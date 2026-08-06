@extends('layouts.app')

@section('title', 'Socios')

@section('content')
    <x-breadcrumbs :items="['Socios' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Socios</h1>
            <a href="{{ route('socios.create') }}" class="btn">+ Nuevo socio</a>
        </div>

        <form action="{{ route('socios.index') }}" method="GET" style="display:flex; gap:8px; margin-bottom:16px;">
            <input type="text" name="q" placeholder="Buscar por nombre o documento..." value="{{ request('q') }}" style="margin-bottom:0;">
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if (request('q'))
                <a class="btn btn-secondary" href="{{ route('socios.index') }}">Limpiar</a>
            @endif
        </form>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Posicion</th>
                    <th>Nivel</th>
                    <th>Equipo</th>
                    <th>Estado</th>
                    <th>Deuda</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($socios as $socio)
                    <tr>
                        <td><a href="{{ route('socios.show', $socio) }}">{{ $socio->nombre_completo }}</a></td>
                        <td>{{ $socio->numero_documento }}</td>
                        <td>{{ $socio->posicion_juego }}</td>
                        <td>{{ ['1' => 'Bueno', '2' => 'Regular', '3' => 'Malo'][$socio->nivel_jugador] }}</td>
                        <td>{{ $socio->equipoActual()?->nombre ?? '-' }}</td>
                        <td><span class="badge badge-{{ $socio->estado }}">{{ ucfirst($socio->estado) }}</span></td>
                        <td class="{{ $socio->deuda_total > 0 ? 'deuda-positiva' : 'deuda-cero' }}">
                            ${{ number_format($socio->deuda_total, 0, ',', '.') }}
                        </td>
                        <td class="actions">
                            <a class="btn btn-sm btn-secondary" href="{{ route('socios.edit', $socio) }}">Editar</a>
                            <form action="{{ route('socios.destroy', $socio) }}" method="POST" onsubmit="return confirm('¿Eliminar este socio?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">No hay socios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $socios->appends(request()->query())->links() }}
@endsection
