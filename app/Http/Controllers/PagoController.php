<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoRequest;
use App\Models\Pago;
use App\Models\Socio;
use App\Services\PagoService;

class PagoController extends Controller
{
    public function __construct(private readonly PagoService $pagos)
    {
    }

    public function store(PagoRequest $request, Socio $socio)
    {
        $pago = $this->pagos->crear($socio, $request->validated());

        return redirect()->route('socios.show', $socio)
            ->with('status', 'Pago registrado.')
            ->with('pago_recibo_id', $pago->id);
    }

    public function destroy(Socio $socio, Pago $pago)
    {
        $this->pagos->eliminar($pago);

        return redirect()->route('socios.show', $socio)->with('status', 'Pago eliminado.');
    }

    public function recibo(Pago $pago)
    {
        $pago->load(['socio', 'equipo', 'cargo.tipoCargo']);

        return view('pagos.recibo', compact('pago'));
    }
}
