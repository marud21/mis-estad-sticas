<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Socio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PagoService
{
    public function crear(Socio $socio, array $datos): Pago
    {
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
