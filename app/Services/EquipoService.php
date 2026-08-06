<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Socio;

class EquipoService
{
    public function crear(array $datos): Equipo
    {
        return Equipo::create($datos);
    }

    public function actualizar(Equipo $equipo, array $datos): Equipo
    {
        $equipo->update($datos);

        return $equipo;
    }

    public function eliminar(Equipo $equipo): void
    {
        $equipo->delete();
    }

    /**
     * Un socio solo puede pertenecer a un equipo a la vez: lo retira de
     * cualquier otro equipo antes de asignarlo al nuevo.
     */
    public function agregarSocio(Equipo $equipo, Socio $socio): void
    {
        $socio->equipos()->sync([$equipo->id]);
    }

    public function quitarSocio(Equipo $equipo, Socio $socio): void
    {
        $equipo->socios()->detach($socio->id);
    }
}
