<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoticiaRequest;
use App\Models\Noticia;
use App\Models\NoticiaAdjunto;
use App\Services\NoticiaService;

class NoticiaController extends Controller
{
    public function __construct(private readonly NoticiaService $noticias)
    {
    }

    public function index()
    {
        $noticias = Noticia::withCount('adjuntos')
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(15);

        return view('noticias.index', compact('noticias'));
    }

    public function create()
    {
        return view('noticias.create');
    }

    public function store(NoticiaRequest $request)
    {
        $datos = $request->safe()->except(['imagen', 'adjuntos']);

        $this->noticias->crear(
            $datos,
            $request->file('imagen'),
            $request->user()?->id,
            $request->file('adjuntos', []),
        );

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia publicada.');
    }

    public function edit(Noticia $noticia)
    {
        $noticia->load('adjuntos');

        return view('noticias.edit', compact('noticia'));
    }

    public function update(NoticiaRequest $request, Noticia $noticia)
    {
        $datos = $request->safe()->except(['imagen', 'adjuntos']);

        $this->noticias->actualizar(
            $noticia,
            $datos,
            $request->file('imagen'),
            $request->file('adjuntos', []),
        );

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia actualizada.');
    }

    public function eliminarAdjunto(Noticia $noticia, NoticiaAdjunto $adjunto)
    {
        abort_unless($adjunto->noticia_id === $noticia->id, 404);

        $this->noticias->eliminarAdjunto($adjunto);

        return back()->with('status', 'Archivo adjunto eliminado.');
    }

    public function destroy(Noticia $noticia)
    {
        $this->noticias->eliminar($noticia);

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia eliminada.');
    }
}
