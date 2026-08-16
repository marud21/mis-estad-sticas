<?php

namespace App\Support;

use Illuminate\Support\Collection;

class AgrupadorFinanciero
{
    /**
     * Agrupa cargos o pagos primero por el año de su fecha, y dentro de
     * cada año, por el torneo asociado (usando "General / sin torneo
     * asignado" para los que no tienen torneo). Los años quedan ordenados
     * del mas reciente al mas antiguo.
     *
     * @return Collection<string, Collection<string, Collection>>
     */
    public static function porAnioYTorneo(Collection $items): Collection
    {
        return $items
            ->groupBy(fn ($item) => $item->fecha->format('Y'))
            ->sortKeysDesc()
            ->map(fn (Collection $grupoAnio) => $grupoAnio->groupBy(
                fn ($item) => $item->torneo->nombre ?? 'General / sin torneo asignado'
            ));
    }
}
