<?php

namespace App\Http\Controllers;

use App\Http\Requests\TorneoRequest;
use App\Models\Torneo;
use App\Services\TorneoService;

class TorneoController extends Controller
{
    public function __construct(private readonly TorneoService $torneos)
    {
    }

    public function index()
    {
        $torneos = Torneo::withCount('equipos')->orderByDesc('fecha_inicio')->paginate(15);

        return view('torneos.index', compact('torneos'));
    }

    public function create()
    {
        return view('torneos.create');
    }

    public function store(TorneoRequest $request)
    {
        $this->torneos->crear($request->validated());

        return redirect()->route('torneos.index')->with('status', 'Torneo creado correctamente.');
    }

    public function show(Torneo $torneo)
    {
        $torneo->loadCount('equipos');
        $equipos = $torneo->equipos()->withCount('socios')->orderBy('nombre')->get();

        return view('torneos.show', compact('torneo', 'equipos'));
    }

    public function edit(Torneo $torneo)
    {
        return view('torneos.edit', compact('torneo'));
    }

    public function update(TorneoRequest $request, Torneo $torneo)
    {
        $this->torneos->actualizar($torneo, $request->validated());

        return redirect()->route('torneos.index')->with('status', 'Torneo actualizado correctamente.');
    }

    public function destroy(Torneo $torneo)
    {
        $this->torneos->eliminar($torneo);

        return redirect()->route('torneos.index')->with('status', 'Torneo eliminado.');
    }
}
