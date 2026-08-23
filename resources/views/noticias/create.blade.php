@extends('layouts.app')

@section('title', 'Nueva noticia')

@section('content')
    <x-breadcrumbs :items="['Noticias' => route('noticias-admin.index'), 'Nueva noticia' => null]" />
    <div class="card">
        <h1>Nueva noticia</h1>
        <form action="{{ route('noticias-admin.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('noticias._form', ['noticia' => null])
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('noticias-admin.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
