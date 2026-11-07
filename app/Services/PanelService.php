<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\CierreCaja;
use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Sancion;
use App\Models\Socio;
use App\Support\FiltroPanel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Los numeros del panel de administracion.
 *
 * Todo sale de consultas agrupadas, no de recorrer modelos: la base
 * tiene miles de cargos y pagos y esta pantalla se abre a cada rato.
 *
 * El rango de fechas se aplica solo a lo que pasa en el tiempo. Los
 * conteos de socios, equipos y deuda son la foto de hoy, porque "cuantos
 * socios activos hay entre marzo y junio" no quiere decir nada.
 */
class PanelService
{
    /** Aplica el filtro de equipos a una consulta que tenga equipo_id. */
    private function soloDelFiltro(Builder $query, FiltroPanel $filtro, string $columna = 'equipo_id'): Builder
    {
        $equipos = $filtro->equiposAlcanzados();

        return $equipos === null ? $query : $query->whereIn($columna, $equipos);
    }

    // ---------------------------------------------------------------
    // Socios
    // ---------------------------------------------------------------

    public function socios(FiltroPanel $filtro): array
    {
        $equipos = $filtro->equiposAlcanzados();

        $base = fn () => Socio::query()->when(
            $equipos !== null,
            fn ($q) => $q->whereHas('equipos', fn ($e) => $e->whereIn('equipos.id', $equipos)),
        );

        $porEstado = $base()
            ->selectRaw('estado, COUNT(*) total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $porCarnet = $base()
            ->selectRaw('carnet, COUNT(*) total')
            ->groupBy('carnet')
            ->pluck('total', 'carnet');

        return [
            'total' => (int) $base()->count(),
            'por_estado' => collect(Socio::ESTADOS_ETIQUETAS)
                ->map(fn ($etiqueta, $valor) => [
                    'etiqueta' => $etiqueta,
                    'valor' => $valor,
                    'total' => (int) ($porEstado[$valor] ?? 0),
                ])
                ->values(),
            'por_carnet' => collect(Socio::CARNETS)
                ->map(fn ($etiqueta, $valor) => [
                    'etiqueta' => $etiqueta,
                    'total' => (int) ($porCarnet[$valor] ?? 0),
                ])
                ->values(),
            // Estos dos no se filtran por equipo: preguntan justamente por
            // quien no tiene equipo o tiene de mas.
            'sin_equipo' => (int) Socio::whereDoesntHave('equipos')
                ->where('estado', '!=', Socio::ESTADO_RETIRADO)
                ->count(),
            'en_varios_equipos' => (int) DB::table('equipo_socio')
                ->select('socio_id')
                ->groupBy('socio_id')
                ->havingRaw('COUNT(*) > 1')
                ->get()
                ->count(),
            'altas_en_rango' => (int) $base()
                ->whereBetween('created_at', [$filtro->desde->startOfDay(), $filtro->hasta->endOfDay()])
                ->count(),
        ];
    }

    // ---------------------------------------------------------------
    // Finanzas
    // ---------------------------------------------------------------

    public function finanzas(FiltroPanel $filtro): array
    {
        $enRango = fn (Builder $q) => $q->whereBetween('fecha', [$filtro->desde->toDateString(), $filtro->hasta->toDateString()]);

        $cargosRango = $this->soloDelFiltro(Cargo::query(), $filtro);
        $pagosRango = $this->soloDelFiltro(Pago::query(), $filtro);

        $facturado = (float) $enRango(clone $cargosRango)->sum('monto');
        $recaudado = (float) $enRango(clone $pagosRango)->sum('valor');

        // La deuda es historica, no del rango: lo que se debe hoy incluye
        // lo que quedo pendiente de antes.
        $cargosTotal = (float) $this->soloDelFiltro(Cargo::query(), $filtro)->sum('monto');
        $pagosTotal = (float) $this->soloDelFiltro(Pago::query(), $filtro)->sum('valor');

        return [
            'facturado_rango' => $facturado,
            'recaudado_rango' => $recaudado,
            'cargos_rango' => (int) $enRango(clone $cargosRango)->count(),
            'pagos_rango' => (int) $enRango(clone $pagosRango)->count(),
            'deuda_total' => $cargosTotal - $pagosTotal,
            'cargos_total' => $cargosTotal,
            'pagos_total' => $pagosTotal,
            'cumplimiento' => $facturado > 0 ? round($recaudado / $facturado * 100) : null,
            'por_tipo_pago' => $enRango($this->soloDelFiltro(Pago::query(), $filtro))
                ->selectRaw('tipo, COUNT(*) total, SUM(valor) valor')
                ->groupBy('tipo')
                ->get()
                ->map(fn ($f) => [
                    'etiqueta' => ucfirst($f->tipo),
                    'total' => (int) $f->total,
                    'valor' => (float) $f->valor,
                ]),
            'por_tipo_cargo' => $enRango($this->soloDelFiltro(Cargo::query(), $filtro))
                ->join('tipos_cargo', 'tipos_cargo.id', '=', 'cargos.tipo_cargo_id')
                ->selectRaw('tipos_cargo.nombre, COUNT(*) total, SUM(cargos.monto) valor')
                ->groupBy('tipos_cargo.nombre')
                ->orderByDesc('valor')
                ->get()
                ->map(fn ($f) => [
                    'etiqueta' => $f->nombre,
                    'total' => (int) $f->total,
                    'valor' => (float) $f->valor,
                ]),
            'por_mes' => $this->recaudoPorMes($filtro),
            'caja' => $this->caja($filtro),
        ];
    }

    /** Recaudo mes a mes dentro del rango, para el grafico de barras. */
    private function recaudoPorMes(FiltroPanel $filtro): Collection
    {
        $pagos = $this->soloDelFiltro(Pago::query(), $filtro)
            ->whereBetween('fecha', [$filtro->desde->toDateString(), $filtro->hasta->toDateString()])
            ->selectRaw("DATE_FORMAT(fecha, '%Y-%m') mes, SUM(valor) valor")
            ->groupBy('mes')
            ->pluck('valor', 'mes');

        // Se recorren todos los meses del rango para que los meses sin
        // ingresos salgan en cero y no desaparezcan del grafico.
        $meses = collect();
        $cursor = $filtro->desde->startOfMonth();

        while ($cursor->lessThanOrEqualTo($filtro->hasta) && $meses->count() < 36) {
            $clave = $cursor->format('Y-m');

            $meses->push([
                'clave' => $clave,
                'etiqueta' => $cursor->translatedFormat('M y'),
                'valor' => (float) ($pagos[$clave] ?? 0),
            ]);

            $cursor = $cursor->addMonth();
        }

        return $meses;
    }

    /** Los cierres de caja del rango. No se filtran por equipo: son del club. */
    private function caja(FiltroPanel $filtro): array
    {
        $cierres = CierreCaja::whereBetween('fecha', [$filtro->desde->toDateString(), $filtro->hasta->toDateString()]);

        return [
            'total' => (int) (clone $cierres)->count(),
            'anulados' => (int) (clone $cierres)->where('anulado', true)->count(),
            'ingresos' => (float) (clone $cierres)->where('anulado', false)->sum('total_ingresos'),
            'gastos' => (float) (clone $cierres)->where('anulado', false)->sum('total_gastos'),
            'ultimo' => CierreCaja::where('anulado', false)->orderByDesc('fecha')->first(),
        ];
    }

    // ---------------------------------------------------------------
    // Deportivo
    // ---------------------------------------------------------------

    public function deportivo(FiltroPanel $filtro): array
    {
        $equipos = $filtro->equiposAlcanzados();

        $partidos = Partido::query()
            ->when($filtro->torneoId, fn ($q) => $q->where('torneo_id', $filtro->torneoId))
            ->when($equipos !== null, fn ($q) => $q->where(
                fn ($s) => $s->whereIn('equipo_local_id', $equipos)->orWhereIn('equipo_visitante_id', $equipos),
            ))
            ->whereBetween('fecha_hora', [$filtro->desde->startOfDay(), $filtro->hasta->endOfDay()]);

        $idsPartidos = (clone $partidos)->pluck('id');

        $incidencias = IncidenciaPartido::whereIn('partido_id', $idsPartidos)
            ->selectRaw('tipo, COUNT(*) total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        return [
            'equipos_total' => (int) Equipo::query()
                ->when($filtro->torneoId, fn ($q) => $q->where('torneo_id', $filtro->torneoId))
                ->when($filtro->categoria, fn ($q) => $q->where('categoria', $filtro->categoria))
                ->count(),
            'equipos_activos' => (int) Equipo::where('estado', Equipo::ESTADO_ACTIVO)
                ->when($filtro->torneoId, fn ($q) => $q->where('torneo_id', $filtro->torneoId))
                ->when($filtro->categoria, fn ($q) => $q->where('categoria', $filtro->categoria))
                ->count(),
            'jugados' => (int) (clone $partidos)->where('estado', Partido::ESTADO_JUGADO)->count(),
            'programados' => (int) (clone $partidos)->where('estado', Partido::ESTADO_PROGRAMADO)->count(),
            'walkovers' => (int) (clone $partidos)->whereNotNull('equipo_ausente_id')->count(),
            'goles' => (int) ($incidencias[IncidenciaPartido::TIPO_GOL] ?? 0),
            'autogoles' => (int) ($incidencias[IncidenciaPartido::TIPO_AUTOGOL] ?? 0),
            'amarillas' => (int) ($incidencias[IncidenciaPartido::TIPO_AMARILLA] ?? 0),
            'rojas' => (int) ($incidencias[IncidenciaPartido::TIPO_ROJA] ?? 0),
            'sanciones_vigentes' => (int) Sancion::vigentes()
                ->when($filtro->torneoId, fn ($q) => $q->where('torneo_id', $filtro->torneoId))
                ->count(),
            'goleadores' => $this->goleadores($idsPartidos),
            'equipos_mas_deben' => $this->equiposQueMasDeben($filtro),
        ];
    }

    /** @param  Collection<int, int>  $idsPartidos */
    private function goleadores(Collection $idsPartidos): Collection
    {
        return IncidenciaPartido::whereIn('partido_id', $idsPartidos)
            ->where('tipo', IncidenciaPartido::TIPO_GOL)
            ->join('socios', 'socios.id', '=', 'incidencias_partido.socio_id')
            ->selectRaw('socios.nombre_completo, COUNT(*) goles')
            ->groupBy('socios.nombre_completo')
            ->orderByDesc('goles')
            ->limit(5)
            ->get();
    }

    /** Los equipos con mas deuda acumulada, que es lo que se persigue. */
    private function equiposQueMasDeben(FiltroPanel $filtro): Collection
    {
        $equipos = $filtro->equiposAlcanzados();

        $cargos = Cargo::selectRaw('equipo_id, SUM(monto) total')
            ->whereNotNull('equipo_id')
            ->when($equipos !== null, fn ($q) => $q->whereIn('equipo_id', $equipos))
            ->groupBy('equipo_id')
            ->pluck('total', 'equipo_id');

        $pagos = Pago::selectRaw('equipo_id, SUM(valor) total')
            ->whereNotNull('equipo_id')
            ->when($equipos !== null, fn ($q) => $q->whereIn('equipo_id', $equipos))
            ->groupBy('equipo_id')
            ->pluck('total', 'equipo_id');

        $nombres = Equipo::whereIn('id', $cargos->keys())->pluck('nombre', 'id');

        return $cargos
            ->map(fn ($monto, $id) => [
                'equipo' => $nombres[$id] ?? 'Equipo eliminado',
                'deuda' => (float) $monto - (float) ($pagos[$id] ?? 0),
            ])
            ->filter(fn ($fila) => $fila['deuda'] > 0)
            ->sortByDesc('deuda')
            ->take(5)
            ->values();
    }

    // ---------------------------------------------------------------
    // Salud de los datos
    // ---------------------------------------------------------------

    /**
     * Huecos y rarezas en la informacion cargada. No son errores del
     * sistema sino cosas que conviene revisar, y que de otro modo no se
     * notan hasta que algo no cuadra.
     */
    public function salud(): Collection
    {
        $hoy = now()->toDateString();

        return collect([
            [
                'asunto' => 'Socios sin fecha de nacimiento',
                'total' => (int) Socio::whereNull('fecha_nacimiento')->count(),
                'porque' => 'No aparecen en el saludo de cumpleaños.',
                'enlace' => route('socios.index'),
            ],
            [
                'asunto' => 'Socios sin carnet registrado',
                'total' => (int) Socio::where('carnet', Socio::CARNET_NO_TIENE)
                    ->where('estado', '!=', Socio::ESTADO_RETIRADO)
                    ->count(),
                'porque' => 'Control de carnet pendiente.',
                'enlace' => route('socios.index', ['carnet' => Socio::CARNET_NO_TIENE]),
            ],
            [
                'asunto' => 'Socios activos sin equipo',
                'total' => (int) Socio::whereDoesntHave('equipos')
                    ->where('estado', Socio::ESTADO_ACTIVO)
                    ->count(),
                'porque' => 'No entran en los cobros por equipo.',
                'enlace' => route('socios.index', ['estado' => Socio::ESTADO_ACTIVO]),
            ],
            [
                'asunto' => 'Pagos con fecha futura',
                'total' => (int) Pago::whereDate('fecha', '>', $hoy)->count(),
                'porque' => 'Suele ser un error de digitacion en la fecha.',
                'enlace' => null,
            ],
            [
                'asunto' => 'Cargos sin equipo asignado',
                'total' => (int) Cargo::whereNull('equipo_id')->count(),
                'porque' => 'No se reflejan en la deuda por equipo.',
                'enlace' => null,
            ],
            [
                'asunto' => 'Partidos jugados sin marcador',
                'total' => (int) Partido::where('estado', Partido::ESTADO_JUGADO)
                    ->where(fn ($q) => $q->whereNull('goles_local')->orWhereNull('goles_visitante'))
                    ->count(),
                'porque' => 'No cuentan en la tabla de posiciones.',
                'enlace' => route('partidos.index'),
            ],
        ])->filter(fn ($fila) => $fila['total'] > 0)->values();
    }
}
