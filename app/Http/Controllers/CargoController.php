<?php

namespace App\Http\Controllers;

use App\Http\Requests\CargoRequest;
use App\Models\Cargo;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Services\CargoService;

class CargoController extends Controller
{
    public function __construct(private readonly CargoService $cargos)
    {
    }

    public function store(CargoRequest $request, Socio $socio)
    {
        $this->cargos->crear($socio, $request->validated());

        return redirect()->route('socios.show', $socio)->with('status', 'Cargo agregado al socio.');
    }

    public function edit(Socio $socio, Cargo $cargo)
    {
        $tiposCargo = TipoCargo::orderBy('nombre')->get();

        return view('socios.cargos.edit', compact('socio', 'cargo', 'tiposCargo'));
    }

    public function update(CargoRequest $request, Socio $socio, Cargo $cargo)
    {
        $this->cargos->actualizar($cargo, $request->validated());

        return redirect()->route('socios.show', $socio)->with('status', 'Cargo actualizado.');
    }

    public function destroy(Socio $socio, Cargo $cargo)
    {
        $this->cargos->eliminar($cargo);

        return redirect()->route('socios.show', $socio)->with('status', 'Cargo eliminado.');
    }
}
