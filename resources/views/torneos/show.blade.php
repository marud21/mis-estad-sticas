@extends('layouts.app')

@section('title', $torneo->nombre)

@section('content')
    <x-breadcrumbs :items="['Torneos' => route('torneos.index'), $torneo->nombre => null]" />
    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">{{ $torneo->nombre }}</h1>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('torneos.edit', $torneo) }}">Editar</a>
                <a class="btn btn-secondary" href="{{ route('torneos.index') }}">Volver</a>
            </div>
        </div>
        <p>
            <strong>Inicio:</strong> {{ optional($torneo->fecha_inicio)->format('d/m/Y') ?? '-' }}
            &nbsp;&mdash;&nbsp;
            <strong>Fin:</strong> {{ optional($torneo->fecha_fin)->format('d/m/Y') ?? '-' }}
        </p>
        <p>{{ $torneo->descripcion }}</p>
    </div>

    <div class="card">
        <h2>Equipos asociados ({{ $torneo->equipos_count }})</h2>
        <table>
            <thead>
                <tr><th>Nombre</th><th>Categoria</th><th>Jugadores</th></tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr>
                        <td><a href="{{ route('equipos.show', $equipo) }}">{{ $equipo->nombre }}</a></td>
                        <td>{{ $equipo->categoria ?? '-' }}</td>
                        <td>{{ $equipo->socios_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Este torneo aun no tiene equipos asociados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
