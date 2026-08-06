<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Socio;

class PagoService
{
    public function crear(Socio $socio, array $datos): Pago
    {
        return $socio->pagos()->create($datos);
    }

    public function actualizar(Pago $pago, array $datos): Pago
    {
        $pago->update($datos);

        return $pago;
    }

    public function eliminar(Pago $pago): void
    {
        $pago->delete();
    }
}
