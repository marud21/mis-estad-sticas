<?php

namespace App\Http\Controllers;

use App\Http\Requests\PagoRequest;
use App\Http\Requests\PagosMultiplesEquipoRequest;
use App\Models\Equipo;
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

    /**
     * Ejecuta en cadena todos los pagos escritos en la pantalla del equipo.
     * La fecha es el dia actual, el equipo es el que se esta viendo y
     * ninguno queda asociado a un cargo en particular.
     */
    public function ejecutarMultiples(PagosMultiplesEquipoRequest $request, Equipo $equipo)
    {
        $pagos = $this->pagos->crearVariosParaEquipo($equipo, $request->validated('pagos'));

        return response()->json([
            'mensaje' => $pagos->count().' pago(s) registrado(s) correctamente.',
            'pagos' => $pagos->map(fn (Pago $pago) => [
                'id' => $pago->id,
                'socio_id' => $pago->socio_id,
                'recibo_url' => route('pagos.recibo', $pago),
            ]),
        ]);
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
