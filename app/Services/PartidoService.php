<?php

namespace App\Services;

use App\Models\Partido;

class PartidoService
{
    public function crear(array $datos): Partido
    {
        return Partido::create($this->conMarcadorCoherente($datos));
    }

    public function actualizar(Partido $partido, array $datos): Partido
    {
        $partido->update($this->conMarcadorCoherente($datos));

        return $partido;
    }

    public function eliminar(Partido $partido): void
    {
        $partido->delete();
    }

    /**
     * Solo un partido jugado conserva marcador: si pasa a aplazado,
     * suspendido o cancelado se limpian los goles para no dejar un
     * resultado fantasma contando en la tabla de posiciones.
     */
    private function conMarcadorCoherente(array $datos): array
    {
        if (($datos['estado'] ?? null) !== Partido::ESTADO_JUGADO) {
            $datos['goles_local'] = null;
            $datos['goles_visitante'] = null;
        }

        return $datos;
    }
}
