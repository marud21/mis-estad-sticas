<?php

namespace App\Services;

use App\Models\CierreCaja;
use App\Models\Pago;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class CierreCajaService
{
    /**
     * Calcula los ingresos en efectivo y transferencia registrados en la
     * fecha dada, a partir de los pagos ya guardados en el sistema.
     */
    public function calcularIngresos(CarbonInterface $fecha): array
    {
        $sumas = Pago::whereDate('fecha', $fecha)
            ->selectRaw('tipo, SUM(valor) as total')
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        $efectivo = (float) ($sumas[Pago::TIPO_EFECTIVO] ?? 0);
        $transferencia = (float) ($sumas[Pago::TIPO_TRANSFERENCIA] ?? 0);

        return [
            'efectivo' => $efectivo,
            'transferencia' => $transferencia,
            'total' => $efectivo + $transferencia,
        ];
    }

    /**
     * Crea el cierre de caja del dia, recalculando los ingresos desde los
     * pagos guardados (no confia en lo que llegue del formulario) y
     * guardando los gastos asociados.
     */
    public function crear(array $datos, array $gastos, ?int $userId): CierreCaja
    {
        return DB::transaction(function () use ($datos, $gastos, $userId) {
            $ingresos = $this->calcularIngresos(Carbon::parse($datos['fecha']));
            $totalGastos = collect($gastos)->sum(fn ($gasto) => (float) $gasto['monto']);

            $cierre = CierreCaja::create([
                'fecha' => $datos['fecha'],
                'total_efectivo' => $ingresos['efectivo'],
                'total_transferencia' => $ingresos['transferencia'],
                'total_ingresos' => $ingresos['total'],
                'total_gastos' => $totalGastos,
                'total_neto_efectivo' => $ingresos['efectivo'] - $totalGastos,
                'notas' => $datos['notas'] ?? null,
                'user_id' => $userId,
            ]);

            foreach ($gastos as $gasto) {
                $cierre->gastos()->create([
                    'descripcion' => $gasto['descripcion'],
                    'monto' => $gasto['monto'],
                ]);
            }

            return $cierre;
        });
    }
}
