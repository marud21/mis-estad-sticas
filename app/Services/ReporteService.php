<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Pago;
use App\Models\Partido;
use App\Models\Socio;
use App\Support\AgrupadorFinanciero;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ReporteService
{
    /**
     * Version en PDF (descargable/compartible) del recibo de pago, para
     * poder adjuntarlo como archivo (ej. al compartir por WhatsApp).
     */
    public function reciboPdf(Pago $pago)
    {
        $pago->load(['socio', 'equipo', 'cargo.tipoCargo']);

        return Pdf::loadView('reportes.recibo', ['pago' => $pago])->setPaper('letter', 'portrait');
    }

    public function socioPdf(Socio $socio)
    {
        $socio->load(['cargos.tipoCargo', 'cargos.torneo', 'cargos.equipo', 'pagos.cargo.tipoCargo', 'pagos.torneo', 'pagos.equipo', 'equipos']);

        $cargosPorAnio = AgrupadorFinanciero::porAnioYTorneo($socio->cargos);
        $pagosPorAnio = AgrupadorFinanciero::porAnioYTorneo($socio->pagos);

        return Pdf::loadView('reportes.socio', ['socio' => $socio, 'cargosPorAnio' => $cargosPorAnio, 'pagosPorAnio' => $pagosPorAnio]);
    }

    public function equipoPdf(Equipo $equipo)
    {
        // Mismo orden alfabetico que la pantalla del equipo, para que el
        // impreso se pueda ir siguiendo renglon por renglon.
        $equipo->load(['socios' => function ($query) {
            $query->withCount('cargos')->orderBy('nombre_completo');
        }]);

        $socios = $equipo->socios->map(function (Socio $socio) {
            $socio->setRelation('cargos', $socio->cargos()->get());
            $socio->setRelation('pagos', $socio->pagos()->get());

            return $socio;
        });

        return Pdf::loadView('reportes.equipo', ['equipo' => $equipo, 'socios' => $socios]);
    }

    /**
     * Tarjetas que recibio cada socio del equipo en su ultimo partido
     * jugado, para resaltarlos en la planilla de cobro: son los que llegan
     * con una deuda nueva por amonestacion o expulsion.
     *
     * @return array{partido: ?Partido, tarjetas: \Illuminate\Support\Collection}
     */
    public function tarjetasDelUltimoPartido(Equipo $equipo): array
    {
        $ultimo = Partido::computables()
            ->where(fn ($q) => $q->where('equipo_local_id', $equipo->id)->orWhere('equipo_visitante_id', $equipo->id))
            ->orderByDesc('fecha_hora')
            ->first();

        if (! $ultimo) {
            return ['partido' => null, 'tarjetas' => collect()];
        }

        $tarjetas = IncidenciaPartido::where('partido_id', $ultimo->id)
            ->where('equipo_id', $equipo->id)
            ->whereIn('tipo', array_keys(IncidenciaPartido::TARJETAS))
            ->get()
            ->groupBy('socio_id')
            ->map(fn ($incidencias) => [
                'amarillas' => $incidencias->where('tipo', IncidenciaPartido::TIPO_AMARILLA)->count(),
                'rojas' => $incidencias->where('tipo', IncidenciaPartido::TIPO_ROJA)->count(),
            ]);

        return ['partido' => $ultimo, 'tarjetas' => $tarjetas];
    }

    public function planillaPagosPdf(Equipo $equipo)
    {
        $equipo->load(['socios' => function ($query) {
            $query->orderBy('nombre_completo');
        }]);

        $socios = $equipo->socios->map(function (Socio $socio) {
            $socio->setRelation('cargos', $socio->cargos()->get());
            $socio->setRelation('pagos', $socio->pagos()->get());

            return $socio;
        });

        $ultimoPartido = $this->tarjetasDelUltimoPartido($equipo);

        return Pdf::loadView('reportes.planilla-pagos', [
            'equipo' => $equipo,
            'socios' => $socios,
            'partidoTarjetas' => $ultimoPartido['partido'],
            'tarjetasPorSocio' => $ultimoPartido['tarjetas'],
        ]);
    }

    public function planillaJuegoPdf(Equipo $equipoLocal, Equipo $equipoVisitante, array $datosPartido)
    {
        // Suspendidos y excluidos siguen siendo jugadores del equipo: su
        // situacion es de pagos, no deportiva, asi que deben poder anotarse
        // en el partido junto a los activos.
        $jugadoresLocal = $equipoLocal->socios()
            ->whereIn('estado', Socio::ESTADOS_EN_PLANILLA)
            ->orderByRaw('numero_camiseta IS NULL, numero_camiseta')
            ->orderBy('nombre_completo')
            ->get();

        $jugadoresVisitante = $equipoVisitante->socios()
            ->whereIn('estado', Socio::ESTADOS_EN_PLANILLA)
            ->orderByRaw('numero_camiseta IS NULL, numero_camiseta')
            ->orderBy('nombre_completo')
            ->get();

        return Pdf::loadView('reportes.planilla-juego', [
            'equipoLocal' => $equipoLocal,
            'equipoVisitante' => $equipoVisitante,
            'jugadoresLocal' => $jugadoresLocal,
            'jugadoresVisitante' => $jugadoresVisitante,
            'datosPartido' => $datosPartido,
        ])->setPaper('letter', 'portrait');
    }

    public function contablePdf(CarbonInterface $desde, CarbonInterface $hasta, string $etiquetaPeriodo)
    {
        // Reportes grandes (miles de filas) son pesados para dompdf; se amplian
        // los limites de esta accion puntual en vez de subirlos globalmente.
        @ini_set('memory_limit', '1536M');
        @set_time_limit(180);

        [$cargos, $pagos, $totalCargos, $totalPagos] = $this->consultarCargosYPagos($desde, $hasta);

        return Pdf::loadView('reportes.contable', [
            'cargos' => $cargos,
            'pagos' => $pagos,
            'totalCargos' => $totalCargos,
            'totalPagos' => $totalPagos,
            'neto' => $totalPagos - $totalCargos,
            'etiquetaPeriodo' => $etiquetaPeriodo,
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }

    /**
     * Consulta cargos y pagos de un periodo trayendo unicamente las columnas
     * que necesita el reporte (via join), en lugar de modelos Eloquent
     * completos con relaciones. Con miles de filas esto reduce mucho el uso
     * de memoria y el tiempo de generacion del PDF.
     */
    public function consultarCargosYPagos(CarbonInterface $desde, CarbonInterface $hasta): array
    {
        $cargos = DB::table('cargos')
            ->join('socios', 'socios.id', '=', 'cargos.socio_id')
            ->join('tipos_cargo', 'tipos_cargo.id', '=', 'cargos.tipo_cargo_id')
            ->whereBetween('cargos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('cargos.fecha')
            ->select([
                DB::raw("DATE_FORMAT(cargos.fecha, '%d/%m/%Y') as fecha_fmt"),
                'socios.nombre_completo as socio_nombre',
                'socios.numero_documento',
                'tipos_cargo.nombre as tipo_nombre',
                'cargos.monto',
            ])
            ->get();

        $pagos = DB::table('pagos')
            ->join('socios', 'socios.id', '=', 'pagos.socio_id')
            ->leftJoin('equipos', 'equipos.id', '=', 'pagos.equipo_id')
            ->leftJoin('cargos', 'cargos.id', '=', 'pagos.cargo_id')
            ->leftJoin('tipos_cargo', 'tipos_cargo.id', '=', 'cargos.tipo_cargo_id')
            ->whereBetween('pagos.fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('pagos.fecha')
            ->select([
                DB::raw("DATE_FORMAT(pagos.fecha, '%d/%m/%Y') as fecha_fmt"),
                'socios.nombre_completo as socio_nombre',
                'socios.numero_documento',
                'pagos.tipo',
                'equipos.nombre as equipo_nombre',
                'tipos_cargo.nombre as cargo_nombre',
                'pagos.valor',
            ])
            ->get();

        return [$cargos, $pagos, $cargos->sum('monto'), $pagos->sum('valor')];
    }
}
