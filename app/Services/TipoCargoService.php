<?php

namespace App\Services;

use App\Models\TipoCargo;

class TipoCargoService
{
    public function crear(array $datos): TipoCargo
    {
        return TipoCargo::create($datos);
    }

    public function actualizar(TipoCargo $tipoCargo, array $datos): TipoCargo
    {
        $tipoCargo->update($datos);

        return $tipoCargo;
    }

    public function eliminar(TipoCargo $tipoCargo): void
    {
        $tipoCargo->delete();
    }
}
