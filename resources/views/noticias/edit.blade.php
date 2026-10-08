@extends('layouts.app')

@section('title', 'Editar noticia')

@section('content')
    <x-breadcrumbs :items="['Noticias' => route('noticias-admin.index'), 'Editar' => null]" />
    <div class="card">
        <h1>Editar noticia</h1>
        <form action="{{ route('noticias-admin.update', $noticia) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('noticias._form', ['noticia' => $noticia])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('noticias-admin.index') }}">Cancelar</a>
        </form>
    </div>

    <script>
        /**
         * El boton de eliminar esta dentro del formulario de la noticia y no
         * se pueden anidar formularios, asi que se arma uno aparte al vuelo.
         */
        function eliminarAdjunto(url, nombre) {
            if (!confirm('¿Eliminar el archivo "' + nombre + '"? Esta accion no se puede deshacer.')) {
                return;
            }

            const formulario = document.createElement('form');
            formulario.method = 'POST';
            formulario.action = url;
            formulario.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">'
                + '<input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(formulario);
            formulario.submit();
        }
    </script>
@endsection
