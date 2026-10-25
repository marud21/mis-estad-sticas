@extends('layouts.app')

@section('title', 'Tabla de posiciones')

@section('content')
    <x-breadcrumbs :items="['Posiciones' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Tabla de posiciones</h1>
            <a class="btn btn-secondary" href="{{ route('partidos.index', ['torneo_id' => $torneo?->id]) }}">Ver partidos</a>
        </div>

        @if ($torneos->isEmpty())
            <div class="alert">
                Primero necesitas crear un torneo y asignarle equipos.
                <a href="{{ route('torneos.create') }}">Crear torneo</a>.
            </div>
        @else
            <form id="filtro-posiciones" action="{{ route('partidos.posiciones') }}" method="GET">
                <select name="torneo_id" onchange="this.form.submit()">
                    @foreach ($torneos as $t)
                        <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Ver</button>
            </form>

            <p style="color:#666; font-size:13px;">
                Calculada con {{ $partidosJugados }} partido(s) jugado(s).
                Victoria 3 puntos, empate 1, derrota 0.
                En caso de empate manda la diferencia de gol y luego los goles a favor.
            </p>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Equipo</th>
                            <th class="text-center" title="Partidos jugados">PJ</th>
                            <th class="text-center" title="Partidos ganados">PG</th>
                            <th class="text-center" title="Partidos empatados">PE</th>
                            <th class="text-center" title="Partidos perdidos">PP</th>
                            <th class="text-center" title="Goles a favor">GF</th>
                            <th class="text-center" title="Goles en contra">GC</th>
                            <th class="text-center" title="Diferencia de gol">DG</th>
                            <th class="text-center" title="Puntos sumados o restados por sanciones o bonificaciones">Ajuste</th>
                            <th class="text-center" title="Puntos">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tabla as $fila)
                            <tr>
                                <td class="text-center">{{ $fila['posicion'] }}</td>
                                <td>{{ $fila['equipo'] }}</td>
                                <td class="text-center">{{ $fila['pj'] }}</td>
                                <td class="text-center">{{ $fila['pg'] }}</td>
                                <td class="text-center">{{ $fila['pe'] }}</td>
                                <td class="text-center">{{ $fila['pp'] }}</td>
                                <td class="text-center">{{ $fila['gf'] }}</td>
                                <td class="text-center">{{ $fila['gc'] }}</td>
                                <td class="text-center">{{ $fila['dg'] > 0 ? '+' : '' }}{{ $fila['dg'] }}</td>
                                <td class="text-center">
                                    @if ($fila['ajuste'] !== 0)
                                        <span class="ajuste {{ $fila['ajuste'] > 0 ? 'positivo' : 'negativo' }}">
                                            {{ $fila['ajuste'] > 0 ? '+' : '' }}{{ $fila['ajuste'] }}
                                        </span>
                                    @else
                                        <span style="color:#bbb;">-</span>
                                    @endif
                                </td>
                                <td class="text-center" style="font-weight:700;">{{ $fila['pts'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11">Este torneo aun no tiene equipos inscritos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p style="font-size:12px; color:#888; margin-top:10px;">
                PJ jugados &middot; PG ganados &middot; PE empatados &middot; PP perdidos &middot;
                GF goles a favor &middot; GC goles en contra &middot; DG diferencia &middot;
                Ajuste puntos por sancion o bonificacion &middot; Pts puntos finales
            </p>

            <button type="button" class="btn-anio-toggle" id="btn-toggle-ajustes" onclick="alternarAjustes()">
                <span class="flecha" id="flecha-ajustes">&#9660;</span> Sumar o restar puntos
                <span style="font-weight:normal; color:#666; font-size:12px;">({{ $ajustes->count() }} ajuste(s) aplicado(s))</span>
            </button>

            <div id="panel-ajustes" class="oculto">
                <p style="font-size:13px; color:#666;">
                    Para sanciones del comite, bonificaciones o cualquier regla del torneo.
                    El ajuste se suma a los puntos del equipo y por lo tanto afecta su posicion.
                </p>

                <form action="{{ route('partidos.ajustes.store') }}" method="POST" id="form-ajuste">
                    @csrf
                    <input type="hidden" name="torneo_id" value="{{ $torneo?->id }}">
                    <div class="grid-4">
                        <div>
                            <label>Equipo</label>
                            <select name="equipo_id" required>
                                <option value="">-- Selecciona --</option>
                                @foreach ($equipos as $e)
                                    <option value="{{ $e->id }}" @selected(old('equipo_id') == $e->id)>{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Puntos</label>
                            <input type="number" name="puntos" min="-50" max="50" required placeholder="-3 o +3" value="{{ old('puntos') }}">
                            <p style="font-size:12px; color:#666; margin-top:-10px;">Negativo descuenta, positivo bonifica.</p>
                        </div>
                        <div>
                            <label>Fecha</label>
                            <input type="date" name="fecha" required value="{{ old('fecha', date('Y-m-d')) }}">
                        </div>
                        <div>
                            <label>Motivo</label>
                            <input type="text" name="motivo" maxlength="255" required placeholder="Ej: no presentarse al partido" value="{{ old('motivo') }}">
                        </div>
                    </div>
                    <button class="btn" type="submit" @disabled($equipos->isEmpty())>Aplicar ajuste</button>
                </form>

                @if ($ajustes->isNotEmpty())
                    <div class="table-scroll" style="margin-top:16px;">
                        <table>
                            <thead>
                                <tr><th>Fecha</th><th>Equipo</th><th class="text-center">Puntos</th><th>Motivo</th><th></th></tr>
                            </thead>
                            <tbody>
                                @foreach ($ajustes as $ajuste)
                                    <tr>
                                        <td>{{ $ajuste->fecha->format('d/m/Y') }}</td>
                                        <td>{{ $ajuste->equipo->nombre ?? '-' }}</td>
                                        <td class="text-center">
                                            <span class="ajuste {{ $ajuste->puntos > 0 ? 'positivo' : 'negativo' }}">{{ $ajuste->puntos_con_signo }}</span>
                                        </td>
                                        <td>{{ $ajuste->motivo }}</td>
                                        <td class="actions">
                                            <form action="{{ route('partidos.ajustes.destroy', $ajuste) }}" method="POST" onsubmit="return confirm('¿Quitar este ajuste de puntos?');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <style>
        .oculto { display: none; }
        .ajuste { font-weight: 700; }
        .ajuste.positivo { color: var(--verde); }
        .ajuste.negativo { color: var(--rojo); }
        .btn-anio-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            background: var(--gris-claro);
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            padding: 10px 14px;
            margin-top: 18px;
            font-size: 15px;
            font-weight: 600;
            color: var(--azul-oscuro);
            cursor: pointer;
            text-align: left;
        }
        .btn-anio-toggle:hover { background: #eaeef3; }
        .btn-anio-toggle .flecha { display: inline-block; font-size: 11px; transition: transform .15s ease; }
        .btn-anio-toggle.colapsado .flecha { transform: rotate(-90deg); }

        #filtro-posiciones {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        #filtro-posiciones > select { width: auto; margin-bottom: 0; }

        @media (max-width: 560px) {
            #filtro-posiciones { flex-direction: column; align-items: stretch; }
            #filtro-posiciones > * { width: 100%; max-width: 100%; }
        }
    </style>
    <script>
        // El panel arranca cerrado: sumar o restar puntos es algo puntual,
        // no lo que se viene a ver todos los dias en la tabla.
        function alternarAjustes() {
            document.getElementById('panel-ajustes').classList.toggle('oculto');
            document.getElementById('btn-toggle-ajustes').classList.toggle('colapsado');
        }

        // Si la validacion rechazo el formulario, se abre para mostrar el error.
        @if ($errors->any())
            alternarAjustes();
        @endif
    </script>
@endsection
