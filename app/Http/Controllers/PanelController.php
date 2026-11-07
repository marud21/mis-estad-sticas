<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Torneo;
use App\Services\PanelService;
use App\Support\FiltroPanel;
use Illuminate\Http\Request;

class PanelController extends Controller
{
    public function __construct(private readonly PanelService $panel)
    {
    }

    public function index(Request $request)
    {
        $filtro = FiltroPanel::desdeLaPeticion($request);

        return view('panel.index', [
            'filtro' => $filtro,
            'socios' => $this->panel->socios($filtro),
            'finanzas' => $this->panel->finanzas($filtro),
            'deportivo' => $this->panel->deportivo($filtro),
            'salud' => $this->panel->salud(),

            // Para los selectores del encabezado.
            'torneos' => Torneo::orderByDesc('fecha_inicio')->orderByDesc('id')->get(),
            'categorias' => Equipo::whereNotNull('categoria')
                ->distinct()
                ->orderBy('categoria')
                ->pluck('categoria'),
            'equipos' => Equipo::when($filtro->torneoId, fn ($q) => $q->where('torneo_id', $filtro->torneoId))
                ->orderBy('nombre')
                ->get(['id', 'nombre']),
        ]);
    }
}
