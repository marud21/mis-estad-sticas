<?php

namespace App\Services;

use App\Models\Noticia;
use App\Models\NoticiaAdjunto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NoticiaService
{
    public function __construct(private readonly ImagenService $imagenes)
    {
    }

    /**
     * @param  array<int, UploadedFile>  $adjuntos
     */
    public function crear(array $datos, ?UploadedFile $imagen, ?int $userId, array $adjuntos = []): Noticia
    {
        $datos['publicado'] = $datos['publicado'] ?? false;
        $datos['user_id'] = $userId;

        if ($imagen) {
            $datos['imagen_path'] = $this->imagenes->guardarComprimida($imagen, 'noticias');
        }

        return DB::transaction(function () use ($datos, $adjuntos) {
            $noticia = Noticia::create($datos);
            $this->guardarAdjuntos($noticia, $adjuntos);

            return $noticia;
        });
    }

    /**
     * @param  array<int, UploadedFile>  $adjuntos
     */
    public function actualizar(Noticia $noticia, array $datos, ?UploadedFile $imagen, array $adjuntos = []): Noticia
    {
        $datos['publicado'] = $datos['publicado'] ?? false;

        if ($imagen) {
            if ($noticia->imagen_path) {
                Storage::disk('public')->delete($noticia->imagen_path);
            }

            $datos['imagen_path'] = $this->imagenes->guardarComprimida($imagen, 'noticias');
        }

        return DB::transaction(function () use ($noticia, $datos, $adjuntos) {
            $noticia->update($datos);

            // Los adjuntos nuevos se suman a los que ya tenia: para quitar
            // uno existente se usa el boton de eliminar de cada archivo.
            $this->guardarAdjuntos($noticia, $adjuntos);

            return $noticia;
        });
    }

    public function eliminar(Noticia $noticia): void
    {
        if ($noticia->imagen_path) {
            Storage::disk('public')->delete($noticia->imagen_path);
        }

        // Los registros se borran en cascada, pero los archivos del disco
        // hay que quitarlos a mano para no dejarlos huerfanos.
        foreach ($noticia->adjuntos as $adjunto) {
            Storage::disk('public')->delete($adjunto->ruta);
        }

        $noticia->delete();
    }

    public function eliminarAdjunto(NoticiaAdjunto $adjunto): void
    {
        Storage::disk('public')->delete($adjunto->ruta);

        $adjunto->delete();
    }

    /**
     * Guarda cada archivo subido y deja el registro con su nombre original,
     * tipo y peso, para poder listarlos sin tener que abrirlos.
     *
     * @param  array<int, UploadedFile>  $adjuntos
     */
    private function guardarAdjuntos(Noticia $noticia, array $adjuntos): void
    {
        foreach (array_filter($adjuntos) as $archivo) {
            $noticia->adjuntos()->create([
                'nombre_original' => $archivo->getClientOriginalName(),
                'ruta' => $archivo->store('noticias/adjuntos', 'public'),
                'tipo_mime' => $archivo->getClientMimeType(),
                'tamano' => $archivo->getSize(),
            ]);
        }
    }
}
