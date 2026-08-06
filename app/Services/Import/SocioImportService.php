<?php

namespace App\Services\Import;

use App\Models\Equipo;
use App\Models\Socio;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SocioImportService
{
    private const NIVELES = ['BUENO' => 1, 'REGULAR' => 2, 'MALO' => 3];

    private const ESTADOS = [
        'ACTIVO' => Socio::ESTADO_ACTIVO,
        'SUSPENDIDO' => Socio::ESTADO_SUSPENDIDO,
        'RETIRADO' => Socio::ESTADO_RETIRADO,
    ];

    private const EQUIPOS_PLACEHOLDER = ['PENDIENTE', 'SIN EQUIPO', 'N/A', 'NA', '-', 'NINGUNO'];

    public function importar(UploadedFile $archivo): ImportResultado
    {
        [, $filas] = CsvReader::leer($archivo);
        $resultado = new ImportResultado();

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            try {
                $this->importarFila($fila);
                $resultado->marcarExito();
            } catch (\Throwable $e) {
                $resultado->marcarError($numeroFila, $e->getMessage());
            }
        }

        return $resultado;
    }

    private function importarFila(array $fila): void
    {
        $documento = CsvReader::normalizarDocumento($fila['CEDULA'] ?? '');
        $nombre = trim($fila['NOMBRE'] ?? '');

        if ($documento === '') {
            throw new \RuntimeException('La columna Cedula esta vacia.');
        }

        if ($nombre === '') {
            throw new \RuntimeException('La columna Nombre esta vacia.');
        }

        DB::transaction(function () use ($fila, $documento, $nombre) {
            $existente = Socio::where('numero_documento', $documento)->first();

            $fechaNacimiento = CsvReader::parsearFecha($fila['FECHA DE NACIMIENTO'] ?? null)
                ?? $existente?->fecha_nacimiento?->toDateString();

            $fechaIngreso = CsvReader::parsearFecha($fila['FECHA DE INGRESO'] ?? null)
                ?? $existente?->fecha_ingreso?->toDateString();

            $socio = Socio::updateOrCreate(
                ['numero_documento' => $documento],
                [
                    'nombre_completo' => $nombre,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'fecha_ingreso' => $fechaIngreso,
                    'entidad_salud' => $this->valorOExistente($fila['ENTIDAD DE SALUD'] ?? '', $existente?->entidad_salud),
                    'celular' => $this->valorOExistente($fila['CONTACTO'] ?? '', $existente?->celular),
                    'tipo_sangre' => $this->valorOExistente($fila['RH'] ?? '', $existente?->tipo_sangre),
                    'direccion_residencia' => $this->valorOExistente($fila['DIRECCION'] ?? '', $existente?->direccion_residencia),
                    'posicion_juego' => $this->valorOExistente($fila['POSICION DE JUEGO'] ?? '', $existente?->posicion_juego),
                    'nivel_jugador' => $this->resolverNivel($fila['NIVEL'] ?? '', $existente?->nivel_jugador),
                    'estado' => $this->resolverEstado($fila['ESTADO'] ?? '', $existente?->estado),
                ]
            );

            $nombreEquipo = trim($fila['EQUIPO'] ?? '');
            $esPlaceholder = in_array(CsvReader::normalizarClave($nombreEquipo), self::EQUIPOS_PLACEHOLDER, true);

            if ($nombreEquipo !== '' && ! $esPlaceholder) {
                $equipo = Equipo::firstOrCreate(['nombre' => $nombreEquipo]);
                $socio->equipos()->sync([$equipo->id]);
            }
        });
    }

    /**
     * Usa el valor del CSV si no esta vacio; si esta vacio, conserva el
     * valor que el socio ya tenia (para no borrar datos reales con celdas
     * en blanco de una importacion parcial).
     */
    private function valorOExistente(string $valorCsv, ?string $valorExistente): string
    {
        $valorCsv = trim($valorCsv);

        return $valorCsv !== '' ? $valorCsv : ($valorExistente ?: 'Sin registrar');
    }

    private function resolverNivel(string $valor, ?int $existente): int
    {
        $valor = CsvReader::normalizarClave($valor);

        if ($valor === '') {
            return $existente ?? Socio::NIVEL_REGULAR;
        }

        if (is_numeric($valor) && in_array((int) $valor, [1, 2, 3], true)) {
            return (int) $valor;
        }

        return self::NIVELES[$valor] ?? ($existente ?? Socio::NIVEL_REGULAR);
    }

    private function resolverEstado(string $valor, ?string $existente): string
    {
        $valor = CsvReader::normalizarClave($valor);

        if ($valor === '') {
            return $existente ?? Socio::ESTADO_ACTIVO;
        }

        return self::ESTADOS[$valor] ?? ($existente ?? Socio::ESTADO_ACTIVO);
    }
}
