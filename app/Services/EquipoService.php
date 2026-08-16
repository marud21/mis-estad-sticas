<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;

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
     * Un socio puede pertenecer a varios equipos a la vez: se agrega este
     * equipo sin quitarlo de los que ya tenia.
     */
    public function agregarSocio(Equipo $equipo, Socio $socio): void
    {
        $socio->equipos()->syncWithoutDetaching([$equipo->id]);
    }

    public function quitarSocio(Equipo $equipo, Socio $socio): void
    {
        $equipo->socios()->detach($socio->id);
    }

    /**
     * Cambia el estado del equipo. Al pasar a "inactivo", los socios
     * activos de ese equipo quedan suspendidos (marcados como suspendidos
     * por el equipo). Al volver a "activo", esos mismos socios se
     * reactivan automaticamente; los que fueron suspendidos por otro
     * motivo no se ven afectados.
     */
    public function cambiarEstado(Equipo $equipo, string $estado): Equipo
    {
        if ($estado === $equipo->estado) {
            return $equipo;
        }

        DB::transaction(function () use ($equipo, $estado) {
            if ($estado === Equipo::ESTADO_INACTIVO) {
                $equipo->socios()
                    ->where('estado', Socio::ESTADO_ACTIVO)
                    ->get()
                    ->each(function (Socio $socio) {
                        $socio->update([
                            'estado' => Socio::ESTADO_SUSPENDIDO,
                            'fecha_cambio_estado' => today(),
                            'suspendido_por_equipo' => true,
                        ]);
                    });
            } else {
                $equipo->socios()
                    ->where('estado', Socio::ESTADO_SUSPENDIDO)
                    ->where('suspendido_por_equipo', true)
                    ->get()
                    ->each(function (Socio $socio) {
                        $socio->update([
                            'estado' => Socio::ESTADO_ACTIVO,
                            'fecha_cambio_estado' => today(),
                            'suspendido_por_equipo' => false,
                        ]);
                    });
            }

            $equipo->update([
                'estado' => $estado,
                'fecha_cambio_estado' => today(),
            ]);
        });

        return $equipo->fresh();
    }
}
