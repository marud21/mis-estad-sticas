<?php

namespace App\Services;

use App\Models\Socio;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Quien cumple años hoy, para saludarlo en la pagina publica.
 *
 * "Hoy" es el dia en la zona horaria de la aplicacion (America/Bogota),
 * no la del servidor, que corre en UTC: de otro modo el saludo aparecria
 * y desapareceria a las 7 de la noche.
 */
class CumpleanosService
{
    /**
     * Socios que cumplen años en la fecha dada (hoy por defecto).
     *
     * No entran los retirados: ya no pertenecen al club. Los que estan en
     * sostenimiento o excluidos si, porque eso es una situacion de pagos y
     * siguen siendo socios.
     *
     * @return Collection<int, Socio>
     */
    public function deHoy(?CarbonInterface $fecha = null): Collection
    {
        $dia = $fecha ? $fecha->copy() : now();

        $query = Socio::where('estado', '!=', Socio::ESTADO_RETIRADO)
            ->with(['equipos:id,nombre'])
            ->orderBy('nombre_completo');

        // Quien nacio un 29 de febrero no tendria cumpleaños casi nunca, asi
        // que en los años que no son bisiestos se le saluda el 28.
        if ($dia->month === 2 && $dia->day === 28 && ! $dia->isLeapYear()) {
            $query->where(function ($q) {
                $q->where(fn ($s) => $s->whereMonth('fecha_nacimiento', 2)->whereDay('fecha_nacimiento', 28))
                    ->orWhere(fn ($s) => $s->whereMonth('fecha_nacimiento', 2)->whereDay('fecha_nacimiento', 29));
            });

            return $query->get();
        }

        return $query
            ->whereMonth('fecha_nacimiento', $dia->month)
            ->whereDay('fecha_nacimiento', $dia->day)
            ->get();
    }
}
