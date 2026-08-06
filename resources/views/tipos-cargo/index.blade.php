@extends('layouts.app')

@section('title', 'Tipos de cargo')

@section('content')
    <x-breadcrumbs :items="['Tipos de cargo' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Tipos de cargo</h1>
            <a href="{{ route('tipos-cargo.create') }}" class="btn">+ Nuevo tipo de cargo</a>
        </div>
        <table>
            <thead>
                <tr><th>Nombre</th><th>Monto por defecto</th><th>Recurrente</th><th>% suspendido</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($tiposCargo as $tipo)
                    <tr>
                        <td>
                            {{ $tipo->nombre }}
                            @if ($tipo->ya_aplicado_este_mes ?? false)
                                <span class="badge badge-suspendido" style="margin-left:6px;">Ya cargada este mes</span>
                            @endif
                        </td>
                        <td>${{ number_format($tipo->monto_default, 0, ',', '.') }}</td>
                        <td>{{ $tipo->es_recurrente ? 'Si' : 'No' }}</td>
                        <td>{{ number_format($tipo->porcentaje_suspendido, 0) }}%</td>
                        <td class="actions">
                            @if ($tipo->es_recurrente)
                                <form action="{{ route('tipos-cargo.aplicar-masivo', $tipo) }}" method="POST" style="display:flex; gap:6px; align-items:center;"
                                      onsubmit="return confirmarCargaMasiva(this, {{ ($tipo->ya_aplicado_este_mes ?? false) ? 'true' : 'false' }}, '{{ $tipo->nombre }}', '{{ $tipo->porcentaje_suspendido }}');">
                                    @csrf
                                    <input type="date" name="fecha" value="{{ date('Y-m-d') }}" style="width:auto; margin-bottom:0;" required>
                                    <button class="btn btn-sm" type="submit">Cargar a todos</button>
                                </form>
                            @endif
                            <a class="btn btn-sm btn-secondary" href="{{ route('tipos-cargo.edit', $tipo) }}">Editar</a>
                            <form action="{{ route('tipos-cargo.destroy', $tipo) }}" method="POST" onsubmit="return confirm('¿Eliminar este tipo de cargo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay tipos de cargo registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script>
        function confirmarCargaMasiva(form, yaAplicado, nombre, porcentaje) {
            if (yaAplicado) {
                return confirm(
                    'Advertencia: la mensualidad "' + nombre + '" ya se cargo este mes.\n' +
                    '¿Esta seguro de que desea continuar y aplicarla de nuevo?'
                );
            }
            return confirm(
                '¿Aplicar "' + nombre + '" a todos los socios activos y suspendidos (estos ultimos al ' + porcentaje + '%)?'
            );
        }
    </script>
@endsection
