@extends('layouts.app')

@section('title', 'Cargos duplicados')

@section('content')
    <x-breadcrumbs :items="['Tipos de cargo' => route('tipos-cargo.index'), 'Cargos duplicados' => null]" />

    <div class="card">
        <h1>Cargos duplicados</h1>
        <p style="color:#666; font-size:14px;">
            Se considera duplicado cuando un mismo socio tiene mas de un cargo con el mismo tipo de cargo, fecha,
            monto y equipo. Al eliminar, se conserva uno por grupo (el que ya tenga pagos asociados, si aplica,
            para no perder ese historial; si ninguno tiene pagos, se conserva el mas antiguo).
        </p>

        @if ($duplicados->isEmpty())
            <p><strong>No se encontraron cargos duplicados.</strong></p>
        @else
            @php $totalAEliminar = $duplicados->sum('a_eliminar'); @endphp

            <div class="alert">
                Se encontraron {{ $duplicados->count() }} grupo(s) de cargos duplicados.
                Se eliminarian <strong>{{ $totalAEliminar }}</strong> cargo(s) en total, dejando uno por grupo.
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th>Tipo de cargo</th>
                            <th>Equipo</th>
                            <th>Fecha</th>
                            <th class="text-right">Monto</th>
                            <th class="text-right">Cuantos hay</th>
                            <th class="text-right">Se eliminarian</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($duplicados as $grupo)
                            <tr>
                                <td><a href="{{ route('socios.show', $grupo->socio_id) }}">{{ $grupo->socio_nombre }}</a></td>
                                <td>{{ $grupo->tipo_cargo_nombre }}</td>
                                <td>{{ $grupo->equipo_nombre }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($grupo->fecha)->format('d/m/Y') }}</td>
                                <td class="text-right">${{ number_format($grupo->monto, 0, ',', '.') }}</td>
                                <td class="text-right">{{ $grupo->total }}</td>
                                <td class="text-right deuda-positiva">{{ $grupo->a_eliminar }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <form action="{{ route('cargos-duplicados.eliminar') }}" method="POST"
                  onsubmit="return confirm('¿Eliminar {{ $totalAEliminar }} cargo(s) duplicado(s)? Esta accion no se puede deshacer.');"
                  style="margin-top:16px;">
                @csrf
                <button class="btn btn-danger" type="submit">Eliminar los {{ $totalAEliminar }} cargo(s) duplicado(s)</button>
            </form>
        @endif
    </div>
@endsection
