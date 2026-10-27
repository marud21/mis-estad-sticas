<?php

namespace App\Http\Controllers;

use App\Models\Torneo;
use App\Services\EstadisticasTorneoService;
use Illuminate\Http\Request;

class EstadisticasPublicasController extends Controller
{
    public function __construct(private readonly EstadisticasTorneoService $estadisticas)
    {
    }

    /** La portada publica: lo primero que se ve son las estadisticas. */
    public function index(Request $request)
    {
        $torneos = Torneo::orderByDesc('fecha_inicio')->orderByDesc('id')->get();

        // Solo se acepta un torneo existente: por defecto, el mas reciente.
        $torneo = $torneos->firstWhere('id', $request->integer('torneo_id')) ?? $torneos->first();

        if (! $torneo) {
            return view('home', [
                'torneos' => $torneos,
                'torneo' => null,
                'programacionPorCategoria' => collect(),
                'posicionesPorCategoria' => collect(),
                'goleadoresPorCategoria' => collect(),
                'sancionados' => collect(),
                'tarjetas' => collect(),
                'vallaPorCategoria' => collect(),
                'resultadosPorCategoria' => collect(),
            ]);
        }

        return view('home', [
            'torneos' => $torneos,
            'torneo' => $torneo,
            'programacionPorCategoria' => $this->estadisticas->programacionPorCategoria($torneo),
            'posicionesPorCategoria' => $this->estadisticas->posicionesPorCategoria($torneo),
            'goleadoresPorCategoria' => $this->estadisticas->goleadoresPorCategoria($torneo),
            'sancionados' => $this->estadisticas->sancionados($torneo),
            'tarjetas' => $this->estadisticas->tarjetas($torneo),
            'vallaPorCategoria' => $this->estadisticas->vallaPorCategoria($torneo),
            'resultadosPorCategoria' => $this->estadisticas->resultadosPorCategoria($torneo),
        ]);
    }
}
