<?php

namespace App\Services;

use App\Models\Socio;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Socios que llevan mucho tiempo sin pagarle a la corporacion.
 *
 * Solo mira a los que todavia pertenecen al club y deberian estar
 * aportando: activos y en sostenimiento. Al retirado y al excluido ya se
 * les tomo una decision, asi que avisar por ellos seria ruido.
 *
 * Esto no retira a nadie ni cambia un solo dato: arma la lista para que
 * alguien la mire y decida. La decision de retirar sigue siendo manual.
 */
class SocioInactivoService
{
    /** Meses sin pagar a partir de los cuales el socio entra en la lista. */
    public const MESES_LIMITE = 4;

    /** Estados que se vigilan: los que siguen perteneciendo al club. */
    public const ESTADOS_VIGILADOS = [
        Socio::ESTADO_ACTIVO,
        Socio::ESTADO_SUSPENDIDO,
    ];

    /**
     * Desde cuando se le cuenta al que nunca ha pagado.
     *
     * Se toma lo mas antiguo entre su primer cargo y su fecha de ingreso.
     * El primer cargo importa porque a los socios que entraron por la
     * importacion de agosto les quedo la fecha de registro de ese dia,
     * aunque llevaran meses en el club: medirlos por ahi los dejaria
     * fuera de la lista teniendo quince cargos sin pagar. Si no hay
     * ninguna de las dos, queda la fecha de registro, que es lo unico
     * que siempre existe.
     */
    private const SQL_DESDE = "CASE
        WHEN c.primer_cargo IS NULL AND s.fecha_ingreso IS NULL THEN DATE(s.created_at)
        ELSE LEAST(COALESCE(c.primer_cargo, s.fecha_ingreso), COALESCE(s.fecha_ingreso, c.primer_cargo))
    END";

    /**
     * La fecha contra la que se mide a cada socio: su ultimo pago, o,
     * si nunca pago, desde cuando se le viene cobrando. Ordenar por aqui
     * de menor a mayor deja arriba al que mas lleva sin pagar.
     *
     * El orden se hace en SQL y no en PHP porque la lista va paginada:
     * si cada pagina se ordenara por su cuenta, un mismo socio podria
     * salir en dos paginas o en ninguna.
     */
    private const SQL_REFERENCIA = 'COALESCE(p.ultimo_pago, '.self::SQL_DESDE.')';

    /** Filas por pagina en la pantalla. */
    public const POR_PAGINA = 25;

    /**
     * Los socios que pasaron del limite, del que mas lleva al que menos.
     *
     * @param  int|null  $meses  Para mirar con otro corte sin tocar el codigo.
     * @param  array  $filtros  q, estado, equipo_id, solo_sin_pagos, con_fecha_adelantada.
     */
    public function detectar(?int $meses = null, array $filtros = []): Collection
    {
        $meses = $this->mesesValidos($meses);
        $hoy = Carbon::today();
        $corte = $hoy->copy()->subMonthsNoOverflow($meses);

        $filas = $this->consultaFiltrada($hoy, $corte, $filtros)->get();

        return $this->armarFilas($filas, $hoy);
    }

    /**
     * Lo mismo que detectar(), pero de a una pagina.
     *
     * La pantalla usa esta: con el corte en un mes la lista pasa de las
     * quinientas filas, y traerlas todas para mostrar veinticinco es
     * trabajo tirado a la basura.
     */
    public function paginar(?int $meses = null, array $filtros = [], ?int $porPagina = null): LengthAwarePaginator
    {
        $meses = $this->mesesValidos($meses);
        $hoy = Carbon::today();
        $corte = $hoy->copy()->subMonthsNoOverflow($meses);

        $pagina = $this->consultaFiltrada($hoy, $corte, $filtros)
            ->paginate($porPagina ?: self::POR_PAGINA)
            ->withQueryString();

        // Se reemplaza el contenido de la pagina por las filas ya armadas,
        // conservando los totales y los enlaces que calculo el paginador.
        return $pagina->setCollection($this->armarFilas(collect($pagina->items()), $hoy));
    }

    /**
     * Los totales del conjunto filtrado completo, no los de una pagina.
     *
     * Van en una consulta aparte a proposito: si se sumaran las filas que
     * se estan mostrando, el encabezado diria "deben $400.000" cuando en
     * realidad eso es lo que deben los veinticinco de esta pagina.
     *
     * @return array{total:int, deuda:float, nunca_han_pagado:int, con_fecha_adelantada:int}
     */
    public function resumen(?int $meses = null, array $filtros = []): array
    {
        $meses = $this->mesesValidos($meses);
        $hoy = Carbon::today();
        $corte = $hoy->copy()->subMonthsNoOverflow($meses);

        $fila = DB::query()
            ->fromSub($this->consultaFiltrada($hoy, $corte, $filtros), 't')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(GREATEST(deuda, 0)), 0) AS deuda')
            ->selectRaw('SUM(ultimo_pago IS NULL) AS nunca_han_pagado')
            ->selectRaw('SUM(pago_futuro IS NOT NULL) AS con_fecha_adelantada')
            ->first();

        return [
            'total' => (int) ($fila->total ?? 0),
            'deuda' => (float) ($fila->deuda ?? 0),
            'nunca_han_pagado' => (int) ($fila->nunca_han_pagado ?? 0),
            'con_fecha_adelantada' => (int) ($fila->con_fecha_adelantada ?? 0),
        ];
    }

    /** La consulta con el corte y los filtros ya puestos, en orden. */
    private function consultaFiltrada(Carbon $hoy, Carbon $corte, array $filtros)
    {
        $consulta = $this->pasadosDelCorte($this->consultaBase($hoy), $corte);

        return $this->aplicarFiltros($consulta, $filtros)
            ->orderByRaw(self::SQL_REFERENCIA.' asc')
            // Desempate fijo: sin esto, dos socios con la misma fecha
            // podrian cambiar de orden entre una pagina y la siguiente.
            ->orderBy('s.id');
    }

    /** Pasa las filas crudas a lo que necesita la pantalla. */
    private function armarFilas(Collection $filas, Carbon $hoy): Collection
    {
        if ($filas->isEmpty()) {
            return collect();
        }

        // Los equipos se cargan de una vez: sin esto seria una consulta
        // por socio solo para poner el nombre del equipo en la tabla.
        $socios = Socio::with('equipos')
            ->whereIn('id', $filas->pluck('id'))
            ->get()
            ->keyBy('id');

        return $filas
            ->map(fn ($fila) => $this->armarFila($fila, $socios->get($fila->id), $hoy))
            ->filter()
            ->values();
    }

    /** Cuantos hay, sin armar la lista entera. Para el aviso del panel. */
    public function contar(?int $meses = null): int
    {
        $meses = $this->mesesValidos($meses);
        $hoy = Carbon::today();
        $corte = $hoy->copy()->subMonthsNoOverflow($meses);

        return (int) $this->pasadosDelCorte($this->consultaBase($hoy), $corte)->count();
    }

    /** Valor para el filtro de equipo cuando se buscan los que no tienen. */
    public const SIN_EQUIPO = 'sin';

    /**
     * Recorta la lista a lo que pidio quien la esta mirando.
     *
     * Son filtros de revision, no de seguridad: lo que esconden sigue
     * estando en la lista sin filtrar. Por eso basta con ignorar en
     * silencio cualquier valor que no se reconozca.
     */
    private function aplicarFiltros($consulta, array $filtros)
    {
        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $consulta->where(function ($q) use ($texto) {
                $q->where('s.nombre_completo', 'like', "%{$texto}%")
                    ->orWhere('s.numero_documento', 'like', "%{$texto}%");
            });
        }

        $estado = (string) ($filtros['estado'] ?? '');
        if (in_array($estado, self::ESTADOS_VIGILADOS, true)) {
            $consulta->where('s.estado', $estado);
        }

        $equipo = (string) ($filtros['equipo_id'] ?? '');
        if ($equipo === self::SIN_EQUIPO) {
            $consulta->whereNotExists(fn ($q) => $q->selectRaw('1')
                ->from('equipo_socio')
                ->whereColumn('equipo_socio.socio_id', 's.id'));
        } elseif ($equipo !== '' && ctype_digit($equipo)) {
            $consulta->whereExists(fn ($q) => $q->selectRaw('1')
                ->from('equipo_socio')
                ->whereColumn('equipo_socio.socio_id', 's.id')
                ->where('equipo_socio.equipo_id', (int) $equipo));
        }

        if (! empty($filtros['solo_sin_pagos'])) {
            $consulta->whereNull('p.ultimo_pago');
        }

        if (! empty($filtros['con_fecha_adelantada'])) {
            $consulta->whereNotNull('p.pago_futuro');
        }

        return $consulta;
    }

    /**
     * Deja solo a los que ya pasaron el corte.
     *
     * Son dos casos distintos y conviene no mezclarlos: el que pago
     * alguna vez se mide por su ultimo pago, y el que nunca ha pagado se
     * mide desde que el club empezo a esperarle plata. A este segundo
     * tambien hay que aplicarle el limite: si no, un socio inscrito ayer
     * aparecia de una vez en la lista.
     */
    private function pasadosDelCorte($consulta, Carbon $corte)
    {
        $fecha = $corte->toDateString();

        return $consulta->where(function ($q) use ($fecha) {
            $q->where(function ($q) use ($fecha) {
                $q->whereNotNull('p.ultimo_pago')->whereRaw('p.ultimo_pago < ?', [$fecha]);
            })->orWhere(function ($q) use ($fecha) {
                $q->whereNull('p.ultimo_pago')->whereRaw(self::SQL_DESDE.' < ?', [$fecha]);
            });
        });
    }

    /**
     * Socios vigilados con la fecha de su ultimo pago.
     *
     * Los pagos con fecha posterior a hoy se dejan fuera del "ultimo
     * pago" a proposito: un pago fechado el mes que viene todavia no ha
     * ocurrido, y tomarlo como bueno haria pasar por al dia a quien no
     * lo esta. Igual se arrastra aparte para poder avisarlo en pantalla,
     * porque casi siempre es un error de digitacion en la fecha.
     */
    private function consultaBase(Carbon $hoy)
    {
        $pagos = DB::table('pagos')
            ->selectRaw('socio_id')
            ->selectRaw('MAX(CASE WHEN fecha <= ? THEN fecha END) AS ultimo_pago', [$hoy->toDateString()])
            ->selectRaw('MAX(CASE WHEN fecha > ? THEN fecha END) AS pago_futuro', [$hoy->toDateString()])
            ->selectRaw('SUM(valor) AS total_pagado')
            ->groupBy('socio_id');

        $cargos = DB::table('cargos')
            ->selectRaw('socio_id, SUM(monto) AS total_cargado, MIN(fecha) AS primer_cargo')
            ->groupBy('socio_id');

        return DB::table('socios as s')
            ->leftJoinSub($pagos, 'p', 'p.socio_id', '=', 's.id')
            ->leftJoinSub($cargos, 'c', 'c.socio_id', '=', 's.id')
            ->whereIn('s.estado', self::ESTADOS_VIGILADOS)
            ->select([
                's.id',
                's.nombre_completo',
                's.numero_documento',
                's.celular',
                's.estado',
                's.fecha_ingreso',
                's.created_at',
                'p.ultimo_pago',
                'p.pago_futuro',
                'c.primer_cargo',
            ])
            ->selectRaw('COALESCE(c.total_cargado, 0) - COALESCE(p.total_pagado, 0) AS deuda')
            // Se calcula aqui y no en PHP para que la pantalla muestre
            // exactamente la misma fecha con la que se filtro.
            ->selectRaw(self::SQL_DESDE.' AS desde_cuando');
    }

    /** Pasa una fila de la consulta a lo que necesita la pantalla. */
    private function armarFila($fila, ?Socio $socio, Carbon $hoy): ?array
    {
        if (! $socio) {
            return null;
        }

        $ultimoPago = $fila->ultimo_pago ? Carbon::parse($fila->ultimo_pago) : null;

        // El que pago alguna vez se mide por su ultimo pago. El que nunca
        // ha pagado, desde cuando el club empezo a esperarle plata, que
        // es lo que calcula SQL_DESDE.
        $desde = $ultimoPago ?? Carbon::parse($fila->desde_cuando);

        $equipo = $socio->equipos->first();

        return [
            'socio' => $socio,
            'id' => (int) $fila->id,
            'nombre' => $fila->nombre_completo,
            'documento' => $fila->numero_documento,
            'celular' => $fila->celular,
            'estado' => $fila->estado,
            'estado_etiqueta' => Socio::ESTADOS_ETIQUETAS[$fila->estado] ?? ucfirst((string) $fila->estado),
            'equipo' => $equipo?->nombre,
            'equipo_id' => $equipo?->id,
            'ultimo_pago' => $ultimoPago,
            'nunca_ha_pagado' => $ultimoPago === null,
            'desde' => $desde,
            'dias_sin_pagar' => (int) $desde->diffInDays($hoy),
            'meses_sin_pagar' => (int) $desde->diffInMonths($hoy),
            'deuda' => (float) $fila->deuda,
            // Tiene un pago fechado adelante: por eso puede parecer al dia
            // en otras pantallas aunque aqui aparezca.
            'pago_futuro' => $fila->pago_futuro ? Carbon::parse($fila->pago_futuro) : null,
        ];
    }

    /** El corte en meses, acotado para que nadie pida algo sin sentido. */
    private function mesesValidos(?int $meses): int
    {
        if ($meses === null) {
            return self::MESES_LIMITE;
        }

        return max(1, min(60, $meses));
    }
}
