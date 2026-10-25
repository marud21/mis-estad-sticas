@extends('layouts.app')

@section('title', 'Programar partido')

@section('content')
    <x-breadcrumbs :items="['Partidos' => route('partidos.index'), 'Programar partido' => null]" />
    <div class="card">
        <h1>Programar partido</h1>

        @if ($equipos->count() < 2)
            <div class="alert alert-error">
                Este torneo tiene {{ $equipos->count() }} equipo(s) inscrito(s). Para programar un partido necesitas al menos dos:
                asignale el torneo a los equipos desde <a href="{{ route('equipos.index') }}">Equipos</a>.
            </div>
        @endif

        <form action="{{ route('partidos.store') }}" method="POST">
            @csrf
            @include('partidos._form', ['partido' => null, 'recargarAlCambiarTorneo' => true])
            <button class="btn" type="submit" @disabled($equipos->count() < 2)>Guardar</button>
            <a class="btn btn-secondary" href="{{ route('partidos.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
