<?php

namespace App\Services;

use App\Models\Torneo;

class TorneoService
{
    public function crear(array $datos): Torneo
    {
        return Torneo::create($datos);
    }

    public function actualizar(Torneo $torneo, array $datos): Torneo
    {
        $torneo->update($datos);

        return $torneo;
    }

    public function eliminar(Torneo $torneo): void
    {
        $torneo->delete();
    }
}
