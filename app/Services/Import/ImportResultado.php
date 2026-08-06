<?php

namespace App\Services\Import;

class ImportResultado
{
    public int $exitosos = 0;

    /** @var array<int, string> */
    public array $errores = [];

    public function marcarExito(): void
    {
        $this->exitosos++;
    }

    public function marcarError(int $numeroFila, string $mensaje): void
    {
        $this->errores[] = "Fila {$numeroFila}: {$mensaje}";
    }

    public function totalFilasConError(): int
    {
        return count($this->errores);
    }
}
