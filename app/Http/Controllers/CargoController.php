<?php

namespace App\Http\Controllers;

use App\Http\Requests\CargoRequest;
use App\Http\Requests\TarjetasEquipoRequest;
use App\Models\Cargo;
use App\Models\Equipo;
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
        $socio->load('equipos');

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

    /**
     * Registra de una sola vez las tarjetas marcadas en la pantalla del
     * equipo. Cada tarjeta es un cargo del tipo elegido (Amarillas/Rojas)
     * con la fecha en que se saco, asociado a ese equipo y su torneo.
     */
    public function ejecutarTarjetas(TarjetasEquipoRequest $request, Equipo $equipo)
    {
        $cargos = $this->cargos->crearTarjetasParaEquipo($equipo, $request->validated('tarjetas'));

        return response()->json([
            'mensaje' => $cargos->count().' tarjeta(s) registrada(s) correctamente.',
            'cargos' => $cargos->map(fn (Cargo $cargo) => [
                'id' => $cargo->id,
                'socio_id' => $cargo->socio_id,
                'monto' => (float) $cargo->monto,
            ]),
        ]);
    }
}
