<?php

namespace App\Services;

use App\Models\CarruselImagen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CarruselService
{
    /** Lo que se acepta al subir. Se usa para validar y para mostrarlo. */
    public const ANCHO_RECOMENDADO = 1600;

    public const ALTO_RECOMENDADO = 600;

    public const ANCHO_MINIMO = 1000;

    /** Kilobytes. Las estaticas se comprimen; el GIF no, por eso pesa menos. */
    public const PESO_MAXIMO_KB = 8192;

    public const PESO_MAXIMO_GIF_KB = 3072;

    public const FORMATOS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(private readonly ImagenService $imagenes)
    {
    }

    /**
     * Guarda una imagen nueva al final del carrusel.
     *
     * El GIF se guarda tal cual llega. Si se pasara por el compresor, que
     * usa GD, se quedaria solo con el primer cuadro y perderia la
     * animacion: en este servidor no hay Imagick para hacerlo bien. Por
     * eso al GIF se le exige menos peso, porque es lo que termina
     * descargando cada visitante.
     */
    public function guardar(UploadedFile $archivo, array $datos, ?int $userId = null): CarruselImagen
    {
        $esGif = strtolower($archivo->getClientOriginalExtension()) === 'gif'
            || $archivo->getMimeType() === 'image/gif';

        $ruta = $esGif
            ? $archivo->store('carrusel', 'public')
            : $this->imagenes->guardarComprimida(
                $archivo,
                'carrusel',
                'public',
                self::ANCHO_RECOMENDADO,
            );

        return CarruselImagen::create([
            'titulo' => $datos['titulo'] ?? null,
            'enlace' => $datos['enlace'] ?? null,
            'imagen_path' => $ruta,
            'orden' => (int) (CarruselImagen::max('orden') ?? 0) + 1,
            'publicado' => true,
            'user_id' => $userId,
        ]);
    }

    /** Cambia el orden, el titulo, el enlace o si se publica. */
    public function actualizar(CarruselImagen $imagen, array $datos): CarruselImagen
    {
        $imagen->update([
            'titulo' => $datos['titulo'] ?? $imagen->titulo,
            'enlace' => $datos['enlace'] ?? null,
            'orden' => isset($datos['orden']) ? (int) $datos['orden'] : $imagen->orden,
            'publicado' => (bool) ($datos['publicado'] ?? false),
        ]);

        return $imagen;
    }

    /** Borra el registro y tambien el archivo, para no dejar basura. */
    public function eliminar(CarruselImagen $imagen): void
    {
        DB::transaction(function () use ($imagen) {
            $ruta = $imagen->imagen_path;
            $imagen->delete();

            if ($ruta && Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }
        });
    }

    /** Las reglas en texto, para mostrarlas donde se sube. */
    public static function reglas(): array
    {
        return [
            'resolucion' => self::ANCHO_RECOMENDADO.' x '.self::ALTO_RECOMENDADO.' pixeles',
            'ancho_minimo' => self::ANCHO_MINIMO.' pixeles de ancho',
            'peso_estatica' => round(self::PESO_MAXIMO_KB / 1024).' MB',
            'peso_gif' => round(self::PESO_MAXIMO_GIF_KB / 1024).' MB',
            'formatos' => strtoupper(implode(', ', self::FORMATOS)),
        ];
    }
}
