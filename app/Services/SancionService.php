<?php

namespace App\Services;

use App\Models\Sancion;

class SancionService
{
    public function crear(array $datos, ?int $userId = null): Sancion
    {
        return Sancion::create($datos + ['user_id' => $userId]);
    }

    public function actualizar(Sancion $sancion, array $datos): Sancion
    {
        $sancion->update($datos);

        return $sancion;
    }

    public function eliminar(Sancion $sancion): void
    {
        $sancion->delete();
    }
}
