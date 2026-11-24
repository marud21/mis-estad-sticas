<?php

namespace App\Http\Controllers;

use App\Models\Noticia;
use App\Models\NoticiaAdjunto;
use App\Support\Permisos;
use Illuminate\Support\Facades\Storage;

class NoticiaPublicaController extends Controller
{
    public function index()
    {
        $noticias = Noticia::where('publicado', true)
            ->withCount('adjuntos')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(9);

        return view('noticias-publicas.index', compact('noticias'));
    }

    public function show(Noticia $noticia)
    {
        abort_unless($noticia->publicado, 404);

        $noticia->load('adjuntos');

        return view('noticias-publicas.show', compact('noticia'));
    }

    /**
     * Entrega un adjunto para verlo en pantalla, no para guardarlo.
     *
     * Los archivos viven en el disco privado, sin direccion propia: este
     * es el unico camino hacia ellos, y antes de entregarlos comprueba
     * que el adjunto sea de esa noticia y que la noticia este publicada.
     *
     * Conviene no engañarse con lo que esto protege. Un archivo que el
     * visitante VE es un archivo que su navegador ya recibio: quien
     * quiera guardarlo puede hacerlo desde las herramientas del propio
     * navegador. Lo que se consigue aqui es que no haya un boton que
     * invite a descargarlo ni una direccion suelta que se pueda pasar
     * por ahi. Para algo que de verdad no pueda salir, el camino no es
     * publicarlo.
     */
    public function adjunto(Noticia $noticia, NoticiaAdjunto $adjunto)
    {
        // El adjunto tiene que ser de ESA noticia. Sin esta linea, los
        // dos identificadores de la direccion serian independientes y
        // bastaria nombrar una noticia publicada para sacar el adjunto
        // de cualquier otra, incluso de un borrador.
        abort_unless($adjunto->noticia_id === $noticia->id, 404);

        // Un borrador solo lo ve quien administra noticias, para poder
        // revisar los adjuntos antes de publicar.
        if (! $noticia->publicado) {
            abort_unless(Permisos::puede(auth()->user(), 'noticias-admin.index'), 404);
        }

        abort_unless($adjunto->existeElArchivo(), 404);

        return Storage::disk(NoticiaAdjunto::DISCO)->response(
            $adjunto->ruta,
            $adjunto->nombre_original,
            [
                /*
                 * El tipo se deduce de la extension, no del que mando
                 * quien subio el archivo.
                 *
                 * El XML se entrega como texto plano a proposito: como
                 * XML, el navegador lo interpreta, y un archivo armado
                 * con mala intencion podria ejecutar codigo dentro de
                 * nuestro propio dominio. Como texto se lee igual y no
                 * se ejecuta nada.
                 */
                'Content-Type' => $adjunto->tipo_servido,

                // Que el navegador no adivine el tipo por el contenido y
                // termine tratando como pagina lo que no lo es.
                'X-Content-Type-Options' => 'nosniff',

                // Nada de lo que haya dentro puede pedir recursos, y solo
                // nuestras propias paginas pueden incrustarlo.
                'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; "
                    ."object-src 'self'; frame-ancestors 'self'; base-uri 'none'",
            ],
            'inline',
        );
    }
}
