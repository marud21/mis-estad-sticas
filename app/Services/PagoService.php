<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Socio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PagoService
{
    /**
     * Crea un pago. Si trae equipo_id pero no torneo_id, toma el torneo
     * del equipo en ese momento (foto del torneo actual: si el equipo
     * cambia de torneo despues, este pago no se reasigna).
     */
    public function crear(Socio $socio, array $datos): Pago
    {
        if (! empty($datos['equipo_id']) && empty($datos['torneo_id'])) {
            $datos['torneo_id'] = Equipo::find($datos['equipo_id'])?->torneo_id;
        }

        return $socio->pagos()->create($datos);
    }

    /**
     * Registra en una sola transaccion los pagos de varios socios de un
     * mismo equipo, todos con fecha de hoy y sin cargo asociado.
     *
     * @param  array<int, array{socio_id:int, valor:float, tipo:string}>  $filas
     * @return Collection<int, Pago>
     */
    public function crearVariosParaEquipo(Equipo $equipo, array $filas): Collection
    {
        return DB::transaction(function () use ($equipo, $filas) {
            return collect($filas)->map(function (array $fila) use ($equipo) {
                $socio = $equipo->socios()->findOrFail($fila['socio_id']);

                return $socio->pagos()->create([
                    'valor' => $fila['valor'],
                    'tipo' => $fila['tipo'],
                    'fecha' => today(),
                    'equipo_id' => $equipo->id,
                    'torneo_id' => $equipo->torneo_id,
                    'cargo_id' => null,
                ]);
            });
        });
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
