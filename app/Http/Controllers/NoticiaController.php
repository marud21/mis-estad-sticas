<?php

namespace App\Http\Controllers;

use App\Http\Requests\NoticiaRequest;
use App\Models\Noticia;
use App\Services\NoticiaService;
use Illuminate\Http\Request;

class NoticiaController extends Controller
{
    public function __construct(private readonly NoticiaService $noticias)
    {
    }

    public function index()
    {
        $noticias = Noticia::orderByDesc('fecha_publicacion')->orderByDesc('id')->paginate(15);

        return view('noticias.index', compact('noticias'));
    }

    public function create()
    {
        return view('noticias.create');
    }

    public function store(NoticiaRequest $request)
    {
        $datos = $request->safe()->except('imagen');

        $this->noticias->crear($datos, $request->file('imagen'), $request->user()?->id);

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia publicada.');
    }

    public function edit(Noticia $noticia)
    {
        return view('noticias.edit', compact('noticia'));
    }

    public function update(NoticiaRequest $request, Noticia $noticia)
    {
        $datos = $request->safe()->except('imagen');

        $this->noticias->actualizar($noticia, $datos, $request->file('imagen'));

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia actualizada.');
    }

    public function destroy(Noticia $noticia)
    {
        $this->noticias->eliminar($noticia);

        return redirect()->route('noticias-admin.index')->with('status', 'Noticia eliminada.');
    }
}
