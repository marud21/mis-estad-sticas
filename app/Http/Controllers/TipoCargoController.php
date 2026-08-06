<?php

namespace App\Http\Controllers;

use App\Http\Requests\AplicarCargoMasivoRequest;
use App\Http\Requests\TipoCargoRequest;
use App\Models\TipoCargo;
use App\Services\CargoService;
use App\Services\TipoCargoService;

class TipoCargoController extends Controller
{
    public function __construct(
        private readonly TipoCargoService $tiposCargo,
        private readonly CargoService $cargos,
    ) {
    }

    public function index()
    {
        $tiposCargo = TipoCargo::orderBy('nombre')->get();

        $tiposCargo->each(function (TipoCargo $tipo) {
            $tipo->ya_aplicado_este_mes = $tipo->es_recurrente
                && $tipo->cargos()
                    ->whereYear('fecha', now()->year)
                    ->whereMonth('fecha', now()->month)
                    ->exists();
        });

        return view('tipos-cargo.index', compact('tiposCargo'));
    }

    public function create()
    {
        return view('tipos-cargo.create');
    }

    public function store(TipoCargoRequest $request)
    {
        $this->tiposCargo->crear($request->validated());

        return redirect()->route('tipos-cargo.index')->with('status', 'Tipo de cargo creado.');
    }

    public function edit(TipoCargo $tipoCargo)
    {
        return view('tipos-cargo.edit', compact('tipoCargo'));
    }

    public function update(TipoCargoRequest $request, TipoCargo $tipoCargo)
    {
        $this->tiposCargo->actualizar($tipoCargo, $request->validated());

        return redirect()->route('tipos-cargo.index')->with('status', 'Tipo de cargo actualizado.');
    }

    public function destroy(TipoCargo $tipoCargo)
    {
        $this->tiposCargo->eliminar($tipoCargo);

        return redirect()->route('tipos-cargo.index')->with('status', 'Tipo de cargo eliminado.');
    }

    public function aplicarMasivo(AplicarCargoMasivoRequest $request, TipoCargo $tipoCargo)
    {
        $total = $this->cargos->aplicarATodosLosActivos(
            $tipoCargo,
            $request->validated('fecha'),
            $request->validated('monto'),
        );

        return back()->with('status', "Cargo aplicado a {$total} socio(s) activo(s).");
    }
}
