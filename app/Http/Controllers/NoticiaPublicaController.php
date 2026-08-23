<?php

namespace App\Http\Controllers;

use App\Models\Noticia;

class NoticiaPublicaController extends Controller
{
    public function index()
    {
        $noticias = Noticia::where('publicado', true)
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->paginate(9);

        return view('noticias-publicas.index', compact('noticias'));
    }

    public function show(Noticia $noticia)
    {
        abort_unless($noticia->publicado, 404);

        return view('noticias-publicas.show', compact('noticia'));
    }
}
