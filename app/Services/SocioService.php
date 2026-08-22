<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SocioService
{
    public function crear(array $datos, array $cargosIniciales = [], ?int $equipoId = null): Socio
    {
        return DB::transaction(function () use ($datos, $cargosIniciales, $equipoId) {
            $socio = Socio::create($datos);

            if ($equipoId) {
                $socio->equipos()->syncWithoutDetaching([$equipoId]);
            }

            // Los cargos iniciales quedan asociados al mismo equipo (y su
            // torneo actual) con el que se registro al socio, si se eligio uno.
            $torneoId = $equipoId ? Equipo::find($equipoId)?->torneo_id : null;

            foreach ($cargosIniciales as $cargo) {
                $socio->cargos()->create([
                    'tipo_cargo_id' => $cargo['tipo_cargo_id'],
                    'equipo_id' => $equipoId,
                    'torneo_id' => $torneoId,
                    'monto' => $cargo['monto'],
                    'fecha' => $cargo['fecha'],
                    'descripcion' => $cargo['descripcion'] ?? null,
                ]);
            }

            return $socio;
        });
    }

    public function actualizar(Socio $socio, array $datos, ?int $equipoId = null, bool $equipoEnviado = false): Socio
    {
        $socio->update($datos);

        // Un socio puede pertenecer a varios equipos: este campo solo
        // agrega el equipo seleccionado sin quitar los demas. Para retirar
        // un equipo se usa el boton "Quitar" en la pantalla de ese equipo.
        if ($equipoEnviado && $equipoId) {
            $socio->equipos()->syncWithoutDetaching([$equipoId]);
        }

        return $socio;
    }

    public function eliminar(Socio $socio): void
    {
        if ($socio->foto_path) {
            Storage::disk('public')->delete($socio->foto_path);
        }

        $socio->delete();
    }

    public function cambiarEstado(Socio $socio, string $estado): Socio
    {
        $datos = ['estado' => $estado];

        if ($estado !== $socio->estado) {
            $datos['fecha_cambio_estado'] = now()->toDateString();
        }

        // Un cambio manual de estado desvincula al socio de la suspension
        // automatica por inactividad del equipo, para que una futura
        // reactivacion del equipo no sobreescriba esta decision manual.
        $datos['suspendido_por_equipo'] = false;

        $socio->update($datos);

        return $socio;
    }
}
