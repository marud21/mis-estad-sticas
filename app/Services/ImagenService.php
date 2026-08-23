<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenService
{
    /**
     * Redimensiona (si hace falta) y recomprime una imagen subida antes de
     * guardarla, para que no ocupe el peso completo de la foto original.
     * Siempre se guarda como JPEG.
     */
    public function guardarComprimida(
        UploadedFile $archivo,
        string $directorio,
        string $disco = 'public',
        int $anchoMaximo = 1000,
        int $calidad = 75,
    ): string {
        $imagen = $this->crearDesdeArchivo($archivo);

        if ($imagen === false) {
            // Si el formato no se pudo procesar con GD, se guarda tal cual.
            return $archivo->store($directorio, $disco);
        }

        $anchoOriginal = imagesx($imagen);
        $altoOriginal = imagesy($imagen);

        if ($anchoOriginal > $anchoMaximo) {
            $altoNuevo = (int) round($altoOriginal * ($anchoMaximo / $anchoOriginal));
            $redimensionada = imagecreatetruecolor($anchoMaximo, $altoNuevo);
            imagecopyresampled($redimensionada, $imagen, 0, 0, 0, 0, $anchoMaximo, $altoNuevo, $anchoOriginal, $altoOriginal);
            imagedestroy($imagen);
            $imagen = $redimensionada;
        }

        $rutaRelativa = trim($directorio, '/').'/'.Str::random(40).'.jpg';
        $rutaAbsoluta = Storage::disk($disco)->path($rutaRelativa);

        if (! is_dir(dirname($rutaAbsoluta))) {
            mkdir(dirname($rutaAbsoluta), 0755, true);
        }

        imagejpeg($imagen, $rutaAbsoluta, $calidad);
        imagedestroy($imagen);

        return $rutaRelativa;
    }

    /**
     * @return \GdImage|false
     */
    private function crearDesdeArchivo(UploadedFile $archivo)
    {
        $ruta = $archivo->getRealPath();

        $imagen = match ($archivo->getMimeType()) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($ruta),
            'image/png' => @imagecreatefrompng($ruta),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : false,
            'image/gif' => @imagecreatefromgif($ruta),
            default => false,
        };

        if ($imagen === false) {
            return false;
        }

        // Aplana el canal alfa (PNG/WebP transparentes) sobre fondo blanco,
        // ya que el JPEG de salida no soporta transparencia.
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $fondo = imagecreatetruecolor($ancho, $alto);
        imagefill($fondo, 0, 0, imagecolorallocate($fondo, 255, 255, 255));
        imagecopy($fondo, $imagen, 0, 0, 0, 0, $ancho, $alto);
        imagedestroy($imagen);

        return $fondo;
    }
}
