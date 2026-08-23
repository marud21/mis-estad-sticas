<?php

namespace App\Services;

use App\Models\Noticia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class NoticiaService
{
    public function __construct(private readonly ImagenService $imagenes)
    {
    }

    public function crear(array $datos, ?UploadedFile $imagen, ?int $userId): Noticia
    {
        $datos['publicado'] = $datos['publicado'] ?? false;
        $datos['user_id'] = $userId;

        if ($imagen) {
            $datos['imagen_path'] = $this->imagenes->guardarComprimida($imagen, 'noticias');
        }

        return Noticia::create($datos);
    }

    public function actualizar(Noticia $noticia, array $datos, ?UploadedFile $imagen): Noticia
    {
        $datos['publicado'] = $datos['publicado'] ?? false;

        if ($imagen) {
            if ($noticia->imagen_path) {
                Storage::disk('public')->delete($noticia->imagen_path);
            }

            $datos['imagen_path'] = $this->imagenes->guardarComprimida($imagen, 'noticias');
        }

        $noticia->update($datos);

        return $noticia;
    }

    public function eliminar(Noticia $noticia): void
    {
        if ($noticia->imagen_path) {
            Storage::disk('public')->delete($noticia->imagen_path);
        }

        $noticia->delete();
    }
}
