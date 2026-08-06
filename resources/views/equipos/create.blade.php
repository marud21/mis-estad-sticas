@extends('layouts.app')

@section('title', 'Nuevo equipo')

@section('content')
    <x-breadcrumbs :items="['Equipos' => route('equipos.index'), 'Nuevo equipo' => null]" />
    <div class="card">
        <h1>Nuevo equipo</h1>
        <form action="{{ route('equipos.store') }}" method="POST">
            @csrf
            @include('equipos._form', ['equipo' => null])
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('equipos.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
