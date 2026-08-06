@extends('layouts.app')

@section('title', 'Editar equipo')

@section('content')
    <x-breadcrumbs :items="['Equipos' => route('equipos.index'), $equipo->nombre => route('equipos.show', $equipo), 'Editar' => null]" />
    <div class="card">
        <h1>Editar equipo</h1>
        <form action="{{ route('equipos.update', $equipo) }}" method="POST">
            @csrf
            @method('PUT')
            @include('equipos._form', ['equipo' => $equipo])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('equipos.show', $equipo) }}">Cancelar</a>
        </form>
    </div>
@endsection
