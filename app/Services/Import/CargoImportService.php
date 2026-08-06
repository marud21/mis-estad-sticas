<?php

namespace App\Services\Import;

use App\Models\Socio;
use App\Models\TipoCargo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CargoImportService
{
    private const MESES = [
        'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4,
        'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8,
        'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12,
    ];

    /** @var array<string,TipoCargo> */
    private array $tiposCache = [];

    /** @var array<string,int> documento => numero de fila donde aparecio primero */
    private array $documentosVistos = [];

    public function importar(UploadedFile $archivo, int $anioMensualidades): ImportResultado
    {
        [, $filas] = CsvReader::leer($archivo);
        $resultado = new ImportResultado();
        $this->documentosVistos = [];

        foreach ($filas as $indice => $fila) {
            $numeroFila = $indice + 2;

            try {
                $creados = $this->importarFila($fila, $anioMensualidades, $numeroFila);
                for ($i = 0; $i < max($creados, 1); $i++) {
                    $resultado->marcarExito();
                }
            } catch (\Throwable $e) {
                $resultado->marcarError($numeroFila, $e->getMessage());
            }
        }

        return $resultado;
    }

    private function importarFila(array $fila, int $anio, int $numeroFila): int
    {
        $documento = CsvReader::normalizarDocumento($fila['DOCUMENTO'] ?? '');

        if ($documento === '') {
            throw new \RuntimeException('La columna Documento esta vacia.');
        }

        if (isset($this->documentosVistos[$documento])) {
            $filaAnterior = $this->documentosVistos[$documento];

            throw new \RuntimeException("Documento {$documento} repetido en el archivo (ya aparecio en la fila {$filaAnterior}). Se omite para no duplicar cargos; revisa cual de las dos filas es la correcta.");
        }

        $this->documentosVistos[$documento] = $numeroFila;

        $socio = Socio::where('numero_documento', $documento)->first();

        if (! $socio) {
            throw new \RuntimeException("No existe ningun socio con documento {$documento}. Importa primero la hoja de socios.");
        }

        $cargos = [];

        $this->agregarSiTiene($cargos, $fila, 'DEUDA ANTIGUA', 'Deuda anterior', now()->toDateString(), 'Deuda anterior');
        $this->agregarSiTiene($cargos, $fila, 'AFILIACION', 'Afiliacion', now()->toDateString(), 'Afiliacion');
        $this->agregarSiTiene($cargos, $fila, 'ASAMBLEA', 'Asamblea', now()->toDateString(), 'Asamblea');
        $this->agregarSiTiene($cargos, $fila, 'INSCRIPCION1', 'Inscripcion', now()->toDateString(), 'Inscripcion 1');
        $this->agregarSiTiene($cargos, $fila, 'INSCRIPCION2', 'Inscripcion', now()->toDateString(), 'Inscripcion 2');
        $this->agregarSiTiene($cargos, $fila, 'AMARILLAS', 'Amarillas', now()->toDateString(), 'Tarjetas amarillas');
        $this->agregarSiTiene($cargos, $fila, 'ROJAS', 'Rojas', now()->toDateString(), 'Tarjetas rojas');

        foreach (self::MESES as $columna => $mes) {
            $fecha = sprintf('%04d-%02d-01', $anio, $mes);
            $this->agregarSiTiene($cargos, $fila, $columna, 'Mensualidad', $fecha, "Mensualidad {$columna}");
        }

        if (empty($cargos)) {
            return 0;
        }

        DB::transaction(function () use ($socio, $cargos) {
            foreach ($cargos as $cargo) {
                $socio->cargos()->create($cargo);
            }
        });

        return count($cargos);
    }

    private function agregarSiTiene(array &$cargos, array $fila, string $columna, string $tipoCargoNombre, string $fecha, string $descripcion): void
    {
        $monto = CsvReader::parsearMonto($fila[$columna] ?? null);

        if ($monto === null || $monto == 0.0) {
            return;
        }

        $tipoCargo = $this->resolverTipoCargo($tipoCargoNombre);

        $cargos[] = [
            'tipo_cargo_id' => $tipoCargo->id,
            'monto' => $monto,
            'fecha' => $fecha,
            'descripcion' => $descripcion,
        ];
    }

    private function resolverTipoCargo(string $nombre): TipoCargo
    {
        if (isset($this->tiposCache[$nombre])) {
            return $this->tiposCache[$nombre];
        }

        $tipo = TipoCargo::whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->first();

        if (! $tipo) {
            throw new \RuntimeException("No existe el tipo de cargo \"{$nombre}\". Creelo primero en Tipos de cargo.");
        }

        return $this->tiposCache[$nombre] = $tipo;
    }
}
