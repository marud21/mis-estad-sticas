<?php

namespace App\Services\Import;

use App\Models\Equipo;
use App\Models\Pago;
use App\Models\Socio;
use Illuminate\Http\UploadedFile;

class PagoImportService
{
    private const TIPOS = [
        'EFECTIVO' => Pago::TIPO_EFECTIVO,
        'TRANSFERENCIA' => Pago::TIPO_TRANSFERENCIA,
    ];

    private const EQUIPOS_PLACEHOLDER = ['PENDIENTE', 'SIN EQUIPO', 'NO EXISTE', 'N/A', 'NA', '-', 'NINGUNO'];

    private const EQUIPOS_EXCLUIR = ['PRUEBA'];

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
        $documento = CsvReader::normalizarDocumento($fila['DOCUMENTO'] ?? '');

        if ($documento === '') {
            throw new \RuntimeException('La columna Documento esta vacia.');
        }

        $nombreEquipo = trim($fila['EQUIPO'] ?? '');

        if (in_array(CsvReader::normalizarClave($nombreEquipo), self::EQUIPOS_EXCLUIR, true)) {
            throw new \RuntimeException("Fila excluida intencionalmente (equipo \"{$nombreEquipo}\", pago de prueba).");
        }

        $socio = Socio::where('numero_documento', $documento)->first();

        if (! $socio) {
            throw new \RuntimeException("No existe ningun socio con documento {$documento}. Importa primero la hoja de socios.");
        }

        $fecha = CsvReader::parsearFecha($fila['FECHA'] ?? null) ?? now()->toDateString();

        $valor = CsvReader::parsearMonto($fila['VALOR'] ?? null);

        if ($valor === null || $valor <= 0) {
            throw new \RuntimeException('Valor invalido o vacio.');
        }

        $tipo = self::TIPOS[CsvReader::normalizarClave($fila['TIPO PAGO'] ?? '')] ?? Pago::TIPO_EFECTIVO;

        $equipoId = null;
        $esPlaceholder = in_array(CsvReader::normalizarClave($nombreEquipo), self::EQUIPOS_PLACEHOLDER, true);

        if ($nombreEquipo !== '' && ! $esPlaceholder) {
            $equipoId = Equipo::firstOrCreate(['nombre' => $nombreEquipo])->id;
        }

        $socio->pagos()->create([
            'valor' => $valor,
            'fecha' => $fecha,
            'tipo' => $tipo,
            'equipo_id' => $equipoId,
        ]);
    }
}
