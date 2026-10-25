<?php

namespace App\Http\Controllers;

use App\Http\Requests\SancionRequest;
use App\Models\Equipo;
use App\Models\Sancion;
use App\Models\Socio;
use App\Models\Torneo;
use App\Services\SancionService;
use Illuminate\Http\Request;

class SancionController extends Controller
{
    public function __construct(private readonly SancionService $sanciones)
    {
    }

    public function index(Request $request)
    {
        $torneos = Torneo::orderByDesc('fecha_inicio')->orderBy('nombre')->get();
        $torneo = $torneos->firstWhere('id', $request->integer('torneo_id')) ?? $torneos->first();

        $sanciones = Sancion::with(['socio', 'equipo'])
            ->when($torneo, fn ($q) => $q->where('torneo_id', $torneo->id))
            ->orderByDesc('fecha_desde')
            ->orderByDesc('id')
            ->paginate(20);

        $equipos = $torneo
            ? Equipo::where('torneo_id', $torneo->id)->orderBy('nombre')->get()
            : collect();

        // Jugadores de los equipos del torneo: son los que pueden sancionarse.
        $jugadores = $equipos->isEmpty()
            ? collect()
            : Socio::whereHas('equipos', fn ($q) => $q->whereIn('equipos.id', $equipos->pluck('id')))
                ->with(['equipos' => fn ($q) => $q->whereIn('equipos.id', $equipos->pluck('id'))])
                ->orderBy('nombre_completo')
                ->get();

        return view('sanciones.index', compact('torneos', 'torneo', 'sanciones', 'equipos', 'jugadores'));
    }

    public function store(SancionRequest $request)
    {
        $sancion = $this->sanciones->crear($request->validated(), $request->user()?->id);

        return redirect()->route('sanciones.index', ['torneo_id' => $sancion->torneo_id])
            ->with('status', "Sancion de {$sancion->fechas} fecha(s) registrada a {$sancion->socio->nombre_completo}.");
    }

    public function destroy(Sancion $sancion)
    {
        $torneoId = $sancion->torneo_id;
        $this->sanciones->eliminar($sancion);

        return redirect()->route('sanciones.index', ['torneo_id' => $torneoId])
            ->with('status', 'Sancion eliminada.');
    }
}
