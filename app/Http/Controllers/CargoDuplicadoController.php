<?php

namespace App\Http\Controllers;

use App\Services\CargoService;

class CargoDuplicadoController extends Controller
{
    public function __construct(private readonly CargoService $cargos)
    {
    }

    public function index()
    {
        $duplicados = $this->cargos->detectarDuplicados();

        return view('cargos-duplicados.index', compact('duplicados'));
    }

    public function eliminar()
    {
        $total = $this->cargos->eliminarDuplicados();

        return redirect()->route('cargos-duplicados.index')->with('status', $total > 0
            ? "Se eliminaron {$total} cargo(s) duplicado(s)."
            : 'No se eliminaron cargos: no se encontraron duplicados sin pagos asociados.');
    }
}
