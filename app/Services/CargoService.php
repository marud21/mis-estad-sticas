<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Socio;
use App\Models\TipoCargo;
use Illuminate\Support\Facades\DB;

class CargoService
{
    public function crear(Socio $socio, array $datos): Cargo
    {
        return $socio->cargos()->create($datos);
    }

    public function actualizar(Cargo $cargo, array $datos): Cargo
    {
        $cargo->update($datos);

        return $cargo;
    }

    public function eliminar(Cargo $cargo): void
    {
        $cargo->delete();
    }

    /**
     * Aplica un cargo recurrente (ej. mensualidad) a todos los socios activos
     * y suspendidos que aun no lo tengan registrado para la fecha indicada.
     * Los socios suspendidos pagan solo el porcentaje configurado en el tipo
     * de cargo (por defecto 25%) sobre el monto total.
     */
    public function aplicarATodosLosActivos(TipoCargo $tipoCargo, string $fecha, ?float $monto = null): int
    {
        $montoBase = $monto ?? (float) $tipoCargo->monto_default;
        $porcentajeSuspendido = (float) $tipoCargo->porcentaje_suspendido;
        $contador = 0;

        DB::transaction(function () use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, &$contador) {
            Socio::whereIn('estado', [Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO])
                ->whereDoesntHave('cargos', function ($query) use ($tipoCargo, $fecha) {
                    $query->where('tipo_cargo_id', $tipoCargo->id)->where('fecha', $fecha);
                })
                ->chunkById(100, function ($socios) use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, &$contador) {
                    foreach ($socios as $socio) {
                        $monto = $socio->estado === Socio::ESTADO_SUSPENDIDO
                            ? round($montoBase * $porcentajeSuspendido / 100, 2)
                            : $montoBase;

                        $descripcion = $socio->estado === Socio::ESTADO_SUSPENDIDO
                            ? "{$tipoCargo->nombre} ({$porcentajeSuspendido}% - suspendido)"
                            : $tipoCargo->nombre;

                        $socio->cargos()->create([
                            'tipo_cargo_id' => $tipoCargo->id,
                            'monto' => $monto,
                            'fecha' => $fecha,
                            'descripcion' => $descripcion,
                        ]);
                        $contador++;
                    }
                });
        });

        return $contador;
    }
}
