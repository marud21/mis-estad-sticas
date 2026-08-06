<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlanillaJuegoRequest;
use App\Models\Equipo;
use App\Services\ReporteService;

class PlanillaJuegoController extends Controller
{
    public function __construct(private readonly ReporteService $reportes)
    {
    }

    public function index()
    {
        $equipos = Equipo::orderBy('nombre')->get();

        return view('planilla-juego.index', compact('equipos'));
    }

    public function generar(PlanillaJuegoRequest $request)
    {
        $datos = $request->validated();

        $equipoLocal = Equipo::findOrFail($datos['equipo_local_id']);
        $equipoVisitante = Equipo::findOrFail($datos['equipo_visitante_id']);

        $pdf = $this->reportes->planillaJuegoPdf($equipoLocal, $equipoVisitante, $datos);

        $nombreArchivo = sprintf(
            'planilla-juego-%s-vs-%s.pdf',
            \Illuminate\Support\Str::slug($equipoLocal->nombre),
            \Illuminate\Support\Str::slug($equipoVisitante->nombre)
        );

        return $pdf->download($nombreArchivo);
    }
}
