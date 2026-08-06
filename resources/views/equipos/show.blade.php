@extends('layouts.app')

@section('title', $equipo->nombre)

@section('content')
    <x-breadcrumbs :items="['Equipos' => route('equipos.index'), $equipo->nombre => null]" />
    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">{{ $equipo->nombre }}</h1>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('equipos.reporte', $equipo) }}">Exportar PDF</a>
                <a class="btn btn-secondary" href="{{ route('equipos.planilla-pagos', $equipo) }}">Planilla de pagos</a>
                <a class="btn btn-secondary" href="{{ route('equipos.edit', $equipo) }}">Editar</a>
                <a class="btn btn-secondary" href="{{ route('equipos.index') }}">Volver</a>
            </div>
        </div>
        <p><strong>Categoria:</strong> {{ $equipo->categoria ?? '-' }}</p>
        <p><strong>Torneo:</strong> {{ $equipo->torneo->nombre ?? 'Sin torneo asignado' }}</p>
        <p>{{ $equipo->descripcion }}</p>
    </div>

    <div class="card">
        <h2>Jugadores</h2>
        <table>
            <thead>
                <tr><th>Nombre</th><th>Posicion</th><th>Nivel</th><th>Estado</th><th>Deuda</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($equipo->socios as $socio)
                    <tr>
                        <td><a href="{{ route('socios.show', $socio) }}">{{ $socio->nombre_completo }}</a></td>
                        <td>{{ $socio->posicion_juego }}</td>
                        <td>{{ ['1' => 'Bueno', '2' => 'Regular', '3' => 'Malo'][$socio->nivel_jugador] }}</td>
                        <td><span class="badge badge-{{ $socio->estado }}">{{ ucfirst($socio->estado) }}</span></td>
                        <td class="{{ $socio->deuda_total > 0 ? 'deuda-positiva' : 'deuda-cero' }}">
                            ${{ number_format($socio->deuda_total, 0, ',', '.') }}
                        </td>
                        <td>
                            <form action="{{ route('equipos.socios.destroy', [$equipo, $socio]) }}" method="POST" onsubmit="return confirm('¿Quitar jugador del equipo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">Sin jugadores asignados.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Agregar jugador</h3>
        <form action="{{ route('equipos.socios.store', $equipo) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
            @csrf
            <select name="socio_id" style="width:auto; margin-bottom:0;" required>
                <option value="">-- Seleccionar socio --</option>
                @foreach ($sociosDisponibles as $socio)
                    <option value="{{ $socio->id }}">
                        {{ $socio->nombre_completo }}
                        @if ($socio->equipoActual())
                            (actualmente en {{ $socio->equipoActual()->nombre }})
                        @endif
                    </option>
                @endforeach
            </select>
            <button class="btn" type="submit">Agregar</button>
        </form>
        <p style="font-size:12px; color:#666; margin-top:6px;">
            Un socio solo puede pertenecer a un equipo. Si seleccionas uno que ya esta en otro equipo, sera retirado de ese equipo automaticamente.
        </p>
    </div>
@endsection
