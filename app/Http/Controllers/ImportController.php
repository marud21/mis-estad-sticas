<?php

namespace App\Http\Controllers;

use App\Services\Import\CargoImportService;
use App\Services\Import\PagoImportService;
use App\Services\Import\SocioImportService;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function __construct(
        private readonly SocioImportService $socios,
        private readonly CargoImportService $cargos,
        private readonly PagoImportService $pagos,
    ) {
    }

    public function index()
    {
        return view('importar.index');
    }

    public function socios(Request $request)
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:csv,txt']]);

        $resultado = $this->socios->importar($request->file('archivo'));

        return back()->with('resultado', $resultado)->with('seccion', 'socios');
    }

    public function cargos(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt'],
            'anio' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);

        $resultado = $this->cargos->importar($request->file('archivo'), (int) $request->input('anio'));

        return back()->with('resultado', $resultado)->with('seccion', 'cargos');
    }

    public function pagos(Request $request)
    {
        $request->validate(['archivo' => ['required', 'file', 'mimes:csv,txt']]);

        $resultado = $this->pagos->importar($request->file('archivo'));

        return back()->with('resultado', $resultado)->with('seccion', 'pagos');
    }
}
