<?php

namespace App\Http\Controllers;

use App\Http\Requests\AplicarCargoMasivoRequest;
use App\Http\Requests\ModificarCargoMasivoRequest;
use App\Http\Requests\TipoCargoRequest;
use App\Models\Equipo;
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

        $equipos = Equipo::orderBy('nombre')->get();
        $categorias = Equipo::whereNotNull('categoria')->distinct()->orderBy('categoria')->pluck('categoria');

        return view('tipos-cargo.index', compact('tiposCargo', 'equipos', 'categorias'));
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
        $total = $this->cargos->aplicarMasivo(
            $tipoCargo,
            $request->validated('fecha'),
            $request->validated('monto'),
            $request->validated('nivel'),
            $request->validated('equipo_id'),
            $request->validated('categoria'),
        );

        return back()->with('status', "Cargo aplicado a {$total} socio(s).");
    }

    public function modificarMasivo(ModificarCargoMasivoRequest $request, TipoCargo $tipoCargo)
    {
        $total = $this->cargos->actualizarMasivo(
            $tipoCargo,
            $request->validated('fecha'),
            $request->validated('monto'),
            $request->validated('nivel'),
            $request->validated('equipo_id'),
            $request->validated('categoria'),
        );

        return back()->with('status', $total > 0
            ? "Cargo modificado en {$total} socio(s)."
            : 'No se encontraron cargos ya aplicados con esos filtros para modificar.');
    }
}
