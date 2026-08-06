@extends('layouts.app')

@section('title', 'Editar socio')

@section('content')
    <x-breadcrumbs :items="['Socios' => route('socios.index'), $socio->nombre_completo => route('socios.show', $socio), 'Editar' => null]" />
    <div class="card">
        <h1>Editar socio</h1>
        <form action="{{ route('socios.update', $socio) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('socios._form', ['socio' => $socio])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('socios.show', $socio) }}">Cancelar</a>
        </form>
    </div>
@endsection
