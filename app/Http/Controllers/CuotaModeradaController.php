<?php

namespace App\Http\Controllers;

use App\Http\Requests\PorcentajeCuotaModeradaRequest;
use App\Models\Socio;
use App\Services\CuotaModeradaService;

class CuotaModeradaController extends Controller
{
    public function __construct(private readonly CuotaModeradaService $cuotas)
    {
    }

    public function index()
    {
        $ultimaFecha = Socio::whereNotNull('cuota_moderada_fecha')->max('cuota_moderada_fecha');
        $porcentaje = $this->cuotas->porcentajeActual();

        $socios = Socio::whereIn('estado', [Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO])
            ->selectRaw('socios.*,
                (SELECT COALESCE(SUM(monto), 0) FROM cargos WHERE cargos.socio_id = socios.id) as total_cargos_calc,
                (SELECT COALESCE(SUM(valor), 0) FROM pagos WHERE pagos.socio_id = socios.id) as total_pagos_calc')
            ->havingRaw('total_cargos_calc - total_pagos_calc > 0')
            ->orderBy('nombre_completo')
            ->paginate(20);

        return view('cuota-moderada.index', compact('socios', 'ultimaFecha', 'porcentaje'));
    }

    public function actualizarPorcentaje(PorcentajeCuotaModeradaRequest $request)
    {
        $this->cuotas->actualizarPorcentaje((float) $request->validated('porcentaje'));

        return redirect()->route('cuota-moderada.index')
            ->with('status', 'Porcentaje de la cuota moderada actualizado. Recalcula la cuota para que se aplique a los socios.');
    }

    public function recalcular()
    {
        $total = $this->cuotas->recalcularTodas();

        return redirect()->route('cuota-moderada.index')
            ->with('status', "Cuota moderada recalculada para {$total} socio(s). Se mantendra fija hasta el proximo recalculo manual.");
    }
}
