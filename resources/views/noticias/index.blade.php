@extends('layouts.app')

@section('title', 'Noticias')

@section('content')
    <x-breadcrumbs :items="['Noticias' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Noticias</h1>
            <a href="{{ route('noticias-admin.create') }}" class="btn">+ Nueva noticia</a>
        </div>
        <table>
            <thead>
                <tr><th>Titulo</th><th>Fecha</th><th>Estado</th><th>Autor</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($noticias as $noticia)
                    <tr>
                        <td>{{ $noticia->titulo }}</td>
                        <td>{{ $noticia->fecha_publicacion->format('d/m/Y') }}</td>
                        <td>
                            @if ($noticia->publicado)
                                <span class="badge badge-activo">Publicada</span>
                            @else
                                <span class="badge badge-suspendido">Borrador</span>
                            @endif
                        </td>
                        <td>{{ $noticia->autor->name ?? '-' }}</td>
                        <td class="actions">
                            @if ($noticia->publicado)
                                <a class="btn btn-sm btn-secondary" href="{{ route('noticias-publicas.show', $noticia) }}" target="_blank">Ver</a>
                            @endif
                            <a class="btn btn-sm btn-secondary" href="{{ route('noticias-admin.edit', $noticia) }}">Editar</a>
                            <form action="{{ route('noticias-admin.destroy', $noticia) }}" method="POST" onsubmit="return confirm('¿Eliminar esta noticia?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay noticias registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $noticias->links() }}
@endsection
