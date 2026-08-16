@extends('layouts.app')

@section('title', 'Equipos')

@section('content')
    <x-breadcrumbs :items="['Equipos' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Equipos</h1>
            <a href="{{ route('equipos.create') }}" class="btn">+ Nuevo equipo</a>
        </div>

        <form action="{{ route('equipos.index') }}" method="GET" style="display:flex; gap:8px; margin-bottom:16px;">
            <input type="text" name="q" placeholder="Buscar por nombre, categoria o torneo..." value="{{ request('q') }}" style="margin-bottom:0;">
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if (request('q'))
                <a class="btn btn-secondary" href="{{ route('equipos.index') }}">Limpiar</a>
            @endif
        </form>

        <table>
            <thead>
                <tr><th>Nombre</th><th>Categoria</th><th>Torneo</th><th>Jugadores</th><th>Estado</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($equipos as $equipo)
                    <tr>
                        <td><a href="{{ route('equipos.show', $equipo) }}">{{ $equipo->nombre }}</a></td>
                        <td>{{ $equipo->categoria ?? '-' }}</td>
                        <td>{{ $equipo->torneo->nombre ?? '-' }}</td>
                        <td>{{ $equipo->socios_count }}</td>
                        <td><span class="badge badge-{{ $equipo->estado }}">{{ ucfirst($equipo->estado) }}</span></td>
                        <td class="actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('equipos.edit', $equipo) }}">Editar</a>
                            <form action="{{ route('equipos.destroy', $equipo) }}" method="POST" onsubmit="return confirm('¿Eliminar este equipo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay equipos registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $equipos->appends(request()->query())->links() }}
@endsection
