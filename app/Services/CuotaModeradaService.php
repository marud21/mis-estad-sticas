<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;

class CuotaModeradaService
{
    /**
     * Porcentaje de la deuda actual que se usa como cuota moderada,
     * configurable desde la pantalla "Cuota moderada" (por defecto 25%).
     */
    public function porcentajeActual(): float
    {
        return (float) Configuracion::obtener(
            Configuracion::PORCENTAJE_CUOTA_MODERADA,
            Configuracion::PORCENTAJE_CUOTA_MODERADA_DEFECTO,
        );
    }

    public function actualizarPorcentaje(float $porcentaje): void
    {
        Configuracion::guardar(Configuracion::PORCENTAJE_CUOTA_MODERADA, (string) $porcentaje);
    }

    /**
     * Recalcula la cuota moderada (porcentaje configurado de la deuda
     * actual) de todos los socios con deuda pendiente, y la deja fija
     * hasta el proximo recalculo manual. Asi el abono minimo sugerido no
     * baja en cada pago que el socio haga durante el mes.
     */
    public function recalcularTodas(): int
    {
        $porcentaje = $this->porcentajeActual();
        $contador = 0;

        DB::transaction(function () use ($porcentaje, &$contador) {
            Socio::whereIn('estado', [Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO])
                ->chunkById(100, function ($socios) use ($porcentaje, &$contador) {
                    foreach ($socios as $socio) {
                        $deuda = $socio->deuda_total;

                        $socio->forceFill([
                            'cuota_moderada' => $deuda > 0 ? round($deuda * ($porcentaje / 100), 2) : 0,
                            'cuota_moderada_fecha' => today(),
                        ])->save();

                        $contador++;
                    }
                });
        });

        return $contador;
    }
}
