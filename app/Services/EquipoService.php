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
     *
     * Contrapartida de quitarSocio(): un socio retirado que vuelve a tener
     * equipo se reactiva, para que vuelva a entrar en los cobros. Los
     * suspendidos y excluidos conservan su estado, porque son decisiones
     * que se tomaron a mano.
     *
     * @return bool true si ademas se reactivo al socio.
     */
    public function agregarSocio(Equipo $equipo, Socio $socio): bool
    {
        $socio->equipos()->syncWithoutDetaching([$equipo->id]);

        if ($socio->estado !== Socio::ESTADO_RETIRADO) {
            return false;
        }

        $socio->update([
            'estado' => Socio::ESTADO_ACTIVO,
            'fecha_cambio_estado' => today(),
            'suspendido_por_equipo' => false,
        ]);

        return true;
    }

    /**
     * Quita al socio de este equipo. Si con eso queda sin ningun equipo,
     * pasa a "retirado" para que deje de entrar en los cobros masivos. Si
     * todavia le quedan otros equipos, conserva su estado y se le sigue
     * cobrando. Solo se retira a los socios activos: los suspendidos y
     * excluidos conservan el estado que se les puso a mano.
     *
     * @return bool true si ademas se retiro al socio.
     */
    public function quitarSocio(Equipo $equipo, Socio $socio): bool
    {
        $equipo->socios()->detach($socio->id);

        if ($socio->estado !== Socio::ESTADO_ACTIVO || $socio->equipos()->count() > 0) {
            return false;
        }

        $socio->update([
            'estado' => Socio::ESTADO_RETIRADO,
            'fecha_cambio_estado' => today(),
            'suspendido_por_equipo' => false,
        ]);

        return true;
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
