<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Socio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SocioService
{
    public function crear(array $datos, array $cargosIniciales = [], ?int $equipoId = null): Socio
    {
        return DB::transaction(function () use ($datos, $cargosIniciales, $equipoId) {
            $socio = Socio::create($datos);

            if ($equipoId) {
                $socio->equipos()->sync([$equipoId]);
            }

            foreach ($cargosIniciales as $cargo) {
                $socio->cargos()->create([
                    'tipo_cargo_id' => $cargo['tipo_cargo_id'],
                    'monto' => $cargo['monto'],
                    'fecha' => $cargo['fecha'],
                    'descripcion' => $cargo['descripcion'] ?? null,
                ]);
            }

            return $socio;
        });
    }

    public function actualizar(Socio $socio, array $datos, ?int $equipoId = null, bool $equipoEnviado = false): Socio
    {
        $socio->update($datos);

        if ($equipoEnviado) {
            $socio->equipos()->sync($equipoId ? [$equipoId] : []);
        }

        return $socio;
    }

    public function eliminar(Socio $socio): void
    {
        if ($socio->foto_path) {
            Storage::disk('public')->delete($socio->foto_path);
        }

        $socio->delete();
    }

    public function cambiarEstado(Socio $socio, string $estado): Socio
    {
        $datos = ['estado' => $estado];

        if ($estado !== $socio->estado) {
            $datos['fecha_cambio_estado'] = now()->toDateString();
        }

        $socio->update($datos);

        return $socio;
    }
}
