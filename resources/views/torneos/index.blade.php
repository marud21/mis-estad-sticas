@extends('layouts.app')

@section('title', 'Torneos')

@section('content')
    <x-breadcrumbs :items="['Torneos' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Torneos</h1>
            <a href="{{ route('torneos.create') }}" class="btn">+ Nuevo torneo</a>
        </div>
        <table>
            <thead>
                <tr><th>Nombre</th><th>Inicio</th><th>Fin</th><th>Equipos</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($torneos as $torneo)
                    <tr>
                        <td><a href="{{ route('torneos.show', $torneo) }}">{{ $torneo->nombre }}</a></td>
                        <td>{{ optional($torneo->fecha_inicio)->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ optional($torneo->fecha_fin)->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $torneo->equipos_count }}</td>
                        <td class="actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('torneos.edit', $torneo) }}">Editar</a>
                            <form action="{{ route('torneos.destroy', $torneo) }}" method="POST" onsubmit="return confirm('¿Eliminar este torneo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay torneos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $torneos->links() }}
@endsection
