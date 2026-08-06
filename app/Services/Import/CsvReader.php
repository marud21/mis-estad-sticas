<?php

namespace App\Services\Import;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CsvReader
{
    /**
     * Lee un CSV exportado desde Excel (BOM, codificacion Windows-1252 o
     * UTF-8, delimitador ; o ,) y devuelve filas asociativas con claves
     * normalizadas (mayusculas, sin tildes, espacios colapsados).
     *
     * @return array{0: array<int, string>, 1: array<int, array<string, string>>}
     */
    public static function leer(UploadedFile $archivo): array
    {
        $contenido = file_get_contents($archivo->getRealPath());
        $contenido = self::aUtf8($contenido);

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $lineas = array_values(array_filter($lineas, fn ($l) => trim($l) !== ''));

        if (empty($lineas)) {
            return [[], []];
        }

        $delimitador = self::detectarDelimitador($lineas[0]);

        $encabezados = str_getcsv($lineas[0], $delimitador);
        $encabezadosNormalizados = array_map([self::class, 'normalizarClave'], $encabezados);

        $filas = [];
        for ($i = 1; $i < count($lineas); $i++) {
            $valores = str_getcsv($lineas[$i], $delimitador);
            $fila = [];
            foreach ($encabezadosNormalizados as $idx => $clave) {
                $fila[$clave] = trim($valores[$idx] ?? '');
            }
            $filas[] = $fila;
        }

        return [$encabezadosNormalizados, $filas];
    }

    /**
     * Quita puntos de miles que Excel agrega a documentos puramente
     * numericos (ej. "88.273.202" -> "88273202"), pero preserva documentos
     * alfanumericos tal cual (ej. "X8", "V24860044").
     */
    public static function normalizarDocumento(string $valor): string
    {
        $valor = trim($valor);

        if (preg_match('/^[\d.]+$/', $valor)) {
            return str_replace('.', '', $valor);
        }

        return $valor;
    }

    public static function normalizarClave(string $texto): string
    {
        $texto = trim($texto);
        $texto = str_replace('_', ' ', $texto);
        $texto = Str::of($texto)->ascii()->upper()->toString();

        return trim(preg_replace('/\s+/', ' ', $texto));
    }

    private const MESES_ES = [
        'ENERO' => 1, 'FEBRERO' => 2, 'MARZO' => 3, 'ABRIL' => 4,
        'MAYO' => 5, 'JUNIO' => 6, 'JULIO' => 7, 'AGOSTO' => 8,
        'SEPTIEMBRE' => 9, 'OCTUBRE' => 10, 'NOVIEMBRE' => 11, 'DICIEMBRE' => 12,
    ];

    /**
     * Convierte texto de fecha a formato Y-m-d, o null si no se puede
     * interpretar de forma confiable (nunca lanza excepcion ni "adivina"
     * fechas invalidas como 13-13-13).
     */
    public static function parsearFecha(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        // Formato largo en español: "martes, 24 de marzo de 2026"
        if (preg_match('/(\d{1,2})\s+de\s+([a-záéíóúñ]+)\s+de\s+(\d{4})/ui', $valor, $m)) {
            $mes = self::MESES_ES[self::normalizarClave($m[2])] ?? null;
            $dia = (int) $m[1];
            $anio = (int) $m[3];

            if ($mes && checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }

            return null;
        }

        // Formato largo en español sin año: "28 de febrero" (se asume 2026)
        if (preg_match('/^(\d{1,2})\s+de\s+([a-záéíóúñ]+)$/ui', $valor, $m)) {
            $mes = self::MESES_ES[self::normalizarClave($m[2])] ?? null;
            $dia = (int) $m[1];

            if ($mes && checkdate($mes, $dia, 2026)) {
                return sprintf('2026-%02d-%02d', $mes, $dia);
            }

            return null;
        }

        // Formatos numericos: d/m/Y, d-m-Y, d/m/y (con respaldo m/d/Y si el dia no es valido como mes)
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})$#', $valor, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $anio = (int) $m[3];
            $anio = $anio < 100 ? 2000 + $anio : $anio;

            if (checkdate($b, $a, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $b, $a);
            }

            if (checkdate($a, $b, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $a, $b);
            }

            return null;
        }

        // Formato Y-m-d
        if (preg_match('#^(\d{4})-(\d{1,2})-(\d{1,2})$#', $valor, $m)) {
            $anio = (int) $m[1];
            $mes = (int) $m[2];
            $dia = (int) $m[3];

            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }

            return null;
        }

        return null;
    }

    public static function parsearMonto(?string $valor): ?float
    {
        $valor = trim((string) $valor);

        if ($valor === '' || $valor === '-') {
            return null;
        }

        $valor = preg_replace('/[^0-9,.\-]/', '', $valor);

        if (str_contains($valor, ',') && str_contains($valor, '.')) {
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);
        } elseif (str_contains($valor, ',')) {
            $valor = str_replace(',', '.', $valor);
        } elseif (substr_count($valor, '.') > 1) {
            $valor = str_replace('.', '', $valor);
        }

        return is_numeric($valor) ? (float) $valor : null;
    }

    private static function aUtf8(string $contenido): string
    {
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($contenido, $bom)) {
            return substr($contenido, strlen($bom));
        }

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            return mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        return $contenido;
    }

    private static function detectarDelimitador(string $primeraLinea): string
    {
        $puntoYComa = substr_count($primeraLinea, ';');
        $coma = substr_count($primeraLinea, ',');

        return $puntoYComa >= $coma ? ';' : ',';
    }
}
