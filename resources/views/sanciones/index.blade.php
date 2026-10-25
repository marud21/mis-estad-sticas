@extends('layouts.app')

@section('title', 'Sanciones')

@section('content')
    <x-breadcrumbs :items="['Sanciones' => null]" />
    <div class="card">
        <h1>Sanciones</h1>
        <p style="color:#666; font-size:14px; margin-top:-8px;">
            Sanciones que impone la corporacion a un jugador por una cantidad de fechas.
            Las fechas cumplidas se cuentan solas con los partidos que juega su equipo, y
            los que aun tienen fechas pendientes aparecen en la pagina publica.
        </p>

        @if ($torneos->isEmpty())
            <div class="alert">
                Primero necesitas crear un torneo y asignarle equipos.
                <a href="{{ route('torneos.create') }}">Crear torneo</a>.
            </div>
        @else
            <form id="filtro-sanciones" action="{{ route('sanciones.index') }}" method="GET">
                <select name="torneo_id" onchange="this.form.submit()">
                    @foreach ($torneos as $t)
                        <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Ver</button>
            </form>

            <h3>Registrar sancion</h3>
            @if ($jugadores->isEmpty())
                <div class="alert alert-error">
                    Este torneo no tiene equipos con jugadores. Asigna el torneo a los equipos desde
                    <a href="{{ route('equipos.index') }}">Equipos</a>.
                </div>
            @else
                <form action="{{ route('sanciones.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="torneo_id" value="{{ $torneo->id }}">
                    <div class="grid-4">
                        <div style="grid-column: span 2;">
                            <label>Jugador</label>
                            <select name="socio_id" id="campo-socio" required>
                                <option value="">-- Selecciona --</option>
                                @foreach ($jugadores as $j)
                                    <option value="{{ $j->id }}" data-equipo="{{ $j->equipos->first()?->id }}" @selected(old('socio_id') == $j->id)>
                                        {{ $j->nombre_completo }} ({{ $j->equipos->first()?->nombre ?? 'sin equipo' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Equipo</label>
                            <select name="equipo_id" id="campo-equipo" required>
                                <option value="">-- Selecciona --</option>
                                @foreach ($equipos as $e)
                                    <option value="{{ $e->id }}" @selected(old('equipo_id') == $e->id)>{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Fechas de sancion</label>
                            <input type="number" name="fechas" min="1" max="100" required value="{{ old('fechas', 1) }}">
                        </div>
                        <div>
                            <label>A partir de</label>
                            <input type="date" name="fecha_desde" required value="{{ old('fecha_desde', date('Y-m-d')) }}">
                            <p style="font-size:12px; color:#666; margin-top:-10px;">Desde esta fecha se empiezan a contar los partidos cumplidos.</p>
                        </div>
                        <div style="grid-column: span 3;">
                            <label>Motivo</label>
                            <input type="text" name="motivo" maxlength="255" required placeholder="Ej: agresion a un rival" value="{{ old('motivo') }}">
                        </div>
                    </div>
                    <button class="btn" type="submit">Registrar sancion</button>
                </form>
            @endif

            <h3 style="margin-top:24px;">Sanciones registradas</h3>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Desde</th><th>Jugador</th><th>Equipo</th><th>Motivo</th>
                            <th class="text-center">Fechas</th><th class="text-center">Cumplidas</th>
                            <th class="text-center">Le faltan</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sanciones as $s)
                            <tr style="{{ $s->vigente ? '' : 'opacity:.6;' }}">
                                <td style="white-space:nowrap;">{{ $s->fecha_desde->format('d/m/Y') }}</td>
                                <td>{{ $s->socio->nombre_completo ?? '-' }}</td>
                                <td>{{ $s->equipo->nombre ?? '-' }}</td>
                                <td>{{ $s->motivo }}</td>
                                <td class="text-center">{{ $s->fechas }}</td>
                                <td class="text-center">{{ $s->fechas_cumplidas }}</td>
                                <td class="text-center">
                                    @if ($s->vigente)
                                        <span class="badge badge-retirado">{{ $s->fechas_restantes }}</span>
                                    @else
                                        <span class="badge badge-activo">Cumplida</span>
                                    @endif
                                </td>
                                <td class="actions">
                                    <form action="{{ route('sanciones.destroy', $s) }}" method="POST" onsubmit="return confirm('¿Eliminar esta sancion?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">No hay sanciones registradas en este torneo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $sanciones->appends(request()->query())->links() }}
        @endif
    </div>

    <style>
        #filtro-sanciones { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        #filtro-sanciones > select { width: auto; margin-bottom: 0; }

        @media (max-width: 560px) {
            #filtro-sanciones { flex-direction: column; align-items: stretch; }
            #filtro-sanciones > * { width: 100%; max-width: 100%; }
        }
    </style>
    <script>
        // Al elegir el jugador se preselecciona su equipo, que es lo normal.
        document.getElementById('campo-socio')?.addEventListener('change', function () {
            const equipo = this.selectedOptions[0]?.dataset.equipo;
            if (equipo) document.getElementById('campo-equipo').value = equipo;
        });
    </script>
@endsection
