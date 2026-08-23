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
@endsection
