<?php

namespace App\Http\Controllers;

use App\Http\Requests\CierreCajaRequest;
use App\Models\CierreCaja;
use App\Services\CierreCajaService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CierreCajaController extends Controller
{
    public function __construct(private readonly CierreCajaService $cierres)
    {
    }

    public function index()
    {
        $cierres = CierreCaja::orderByDesc('fecha')->paginate(15);

        return view('cierre-caja.index', compact('cierres'));
    }

    public function create(Request $request)
    {
        $fecha = $request->filled('fecha') ? Carbon::parse($request->input('fecha')) : today();
        $ingresos = $this->cierres->calcularIngresos($fecha);
        $yaExiste = CierreCaja::whereDate('fecha', $fecha)->exists();

        return view('cierre-caja.create', compact('fecha', 'ingresos', 'yaExiste'));
    }

    public function store(CierreCajaRequest $request)
    {
        $datos = $request->safe()->only(['fecha', 'notas']);
        $gastos = collect($request->input('gastos', []))
            ->filter(fn ($gasto) => filled($gasto['descripcion'] ?? null) && filled($gasto['monto'] ?? null))
            ->values()
            ->all();

        $cierre = $this->cierres->crear($datos, $gastos, $request->user()?->id);

        return redirect()->route('cierre-caja.show', $cierre)->with('status', 'Cierre de caja guardado correctamente.');
    }

    public function show(CierreCaja $cierreCaja)
    {
        $cierreCaja->load('gastos', 'usuario');

        return view('cierre-caja.show', ['cierre' => $cierreCaja]);
    }
}
