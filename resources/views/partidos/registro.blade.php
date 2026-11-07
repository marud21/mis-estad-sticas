@extends('layouts.app')

@section('title', 'Registrar resultado')

@php
    // Cantidades ya registradas, para poder corregir un resultado cargado.
    $cantidad = function ($socioId, $tipo) use ($partido) {
        $previo = old("jugadores.{$socioId}.cantidades.{$tipo}");
        if ($previo !== null) {
            return $previo;
        }

        return $partido->incidencias
            ->where('socio_id', $socioId)
            ->where('tipo', $tipo)
            ->count() ?: '';
    };
@endphp

@section('content')
    <x-breadcrumbs :items="['Partidos' => route('partidos.index'), 'Registrar resultado' => null]" />

    <div class="card">
        <div class="card-header" style="margin-bottom:0;">
            <h1 style="margin:0 0 4px;">Registrar resultado</h1>
            <a class="btn btn-secondary" href="{{ route('partidos.planilla', $partido) }}">Planilla de juego</a>
        </div>
        <p style="color:#666; font-size:14px; margin-top:0;">
            <strong>{{ $partido->equipoLocal->nombre ?? '-' }}</strong> vs
            <strong>{{ $partido->equipoVisitante->nombre ?? '-' }}</strong>
            &middot; {{ $partido->fecha_hora->format('d/m/Y H:i') }}
            @if ($partido->fase) &middot; {{ $partido->fase }} @endif
        </p>

        @if (session('aviso_doble_cobro'))
            <div class="alert alert-error">
                <strong>Atencion: posible cobro doble.</strong><br>
                Estos socios ya tienen una tarjeta cobrada el {{ $partido->fecha_hora->format('d/m/Y') }} por fuera de este partido:
                <strong>{{ implode(', ', session('aviso_doble_cobro')) }}</strong>.<br>
                Si continuas se les generara otro cargo. Revisa sus cargos o marca la casilla de confirmacion al final para seguir.
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        {{-- Este aviso no aplica al walkover, donde el marcador se escribe
             a mano, asi que se oculta junto con las tablas de jugadores. --}}
        <div class="alert" id="aviso-normal">
            El marcador se calcula solo con los goles que cargues (un autogol suma al rival).
            Cada tarjeta le genera al socio su cargo correspondiente, con el mismo monto del cobro de tarjetas.
            Si vuelves a registrar este partido, se reemplaza lo anterior.
        </div>

        <form action="{{ route('partidos.registro.guardar', $partido) }}" method="POST">
            @csrf

            @php($esWalkover = (bool) old('walkover', $partido->es_walkover))

            {{-- Walkover: al marcarlo se ocultan las tablas de jugadores,
                 porque no se jugo, y se pide quien falto y el marcador. --}}
            <label class="walkover-interruptor">
                <input type="checkbox" name="walkover" value="1" id="campo-walkover" @checked($esWalkover)>
                <span>Este partido fue por <strong>walkover</strong> (un equipo no se presento)</span>
            </label>

            <div class="walkover-datos" id="bloque-walkover" @if (! $esWalkover) hidden @endif>
                <div class="grid-2">
                    <div>
                        <label for="equipo_ausente_id">Equipo que no se presento</label>
                        <select name="equipo_ausente_id" id="equipo_ausente_id">
                            <option value="">-- Selecciona --</option>
                            @foreach ([$partido->equipoLocal, $partido->equipoVisitante] as $equipo)
                                @if ($equipo)
                                    <option value="{{ $equipo->id }}"
                                            @selected((int) old('equipo_ausente_id', $partido->equipo_ausente_id) === $equipo->id)>
                                        {{ $equipo->nombre }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Marcador final</label>
                        <div class="walkover-marcador">
                            <input type="number" name="goles_local" min="0" max="99"
                                   value="{{ old('goles_local', $esWalkover ? $partido->goles_local : null) }}"
                                   aria-label="Goles de {{ $partido->equipoLocal->nombre ?? 'local' }}">
                            <span>-</span>
                            <input type="number" name="goles_visitante" min="0" max="99"
                                   value="{{ old('goles_visitante', $esWalkover ? $partido->goles_visitante : null) }}"
                                   aria-label="Goles de {{ $partido->equipoVisitante->nombre ?? 'visitante' }}">
                        </div>
                        <p class="walkover-nota">
                            Izquierda: {{ $partido->equipoLocal->nombre ?? 'local' }} &middot;
                            derecha: {{ $partido->equipoVisitante->nombre ?? 'visitante' }}.
                        </p>
                    </div>
                </div>
                <p class="walkover-nota">
                    No se registran goles ni tarjetas, asi que no se le genera ningun cargo a nadie.
                    El partido cuenta en la tabla de posiciones con el marcador que escribas.
                </p>
            </div>

            <div id="bloque-normal" @if ($esWalkover) hidden @endif>
            @foreach ([['equipo' => $partido->equipoLocal, 'jugadores' => $jugadoresLocal], ['equipo' => $partido->equipoVisitante, 'jugadores' => $jugadoresVisitante]] as $lado)
                @php($equipo = $lado['equipo'])
                <h2 style="margin-top:22px;">{{ $equipo->nombre ?? 'Equipo' }}</h2>

                @if ($lado['jugadores']->isEmpty())
                    <p style="color:#666;">Este equipo no tiene jugadores registrados.</p>
                @else
                    <div class="table-scroll">
                        <table class="tabla-registro">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Jugador</th>
                                    <th class="text-center">Goles</th>
                                    <th class="text-center">Autogoles</th>
                                    <th class="text-center">Amarillas</th>
                                    <th class="text-center">Rojas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lado['jugadores'] as $jugador)
                                    <tr>
                                        <td class="text-center">{{ $jugador->numero_camiseta ?? '-' }}</td>
                                        <td>
                                            {{ $jugador->nombre_completo }}
                                            <input type="hidden" name="jugadores[{{ $jugador->id }}][equipo_id]" value="{{ $equipo->id }}">
                                        </td>
                                        @foreach ([\App\Models\IncidenciaPartido::TIPO_GOL, \App\Models\IncidenciaPartido::TIPO_AUTOGOL, \App\Models\IncidenciaPartido::TIPO_AMARILLA, \App\Models\IncidenciaPartido::TIPO_ROJA] as $tipo)
                                            <td class="text-center">
                                                <input type="number" min="0" max="20" placeholder="0"
                                                       class="celda-cantidad {{ in_array($tipo, [\App\Models\IncidenciaPartido::TIPO_GOL, \App\Models\IncidenciaPartido::TIPO_AUTOGOL], true) ? 'cuenta-gol' : '' }}"
                                                       data-equipo="{{ $equipo->id === $partido->equipo_local_id ? 'local' : 'visitante' }}"
                                                       data-tipo="{{ $tipo }}"
                                                       name="jugadores[{{ $jugador->id }}][cantidades][{{ $tipo }}]"
                                                       value="{{ $cantidad($jugador->id, $tipo) }}">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endforeach

            <div class="marcador-resumen">
                Marcador: <span id="marcador">0 - 0</span>
            </div>

            {{-- La figura del partido. Es opcional: si se deja sin elegir,
                 el partido simplemente no aporta nadie a la jornada. --}}
            <div class="figura">
                <label for="jugador_partido_id">&#11088; Jugador del partido</label>
                <select name="jugador_partido_id" id="jugador_partido_id">
                    <option value="">-- Sin eleccion --</option>
                    @foreach ([['equipo' => $partido->equipoLocal, 'jugadores' => $jugadoresLocal], ['equipo' => $partido->equipoVisitante, 'jugadores' => $jugadoresVisitante]] as $lado)
                        <optgroup label="{{ $lado['equipo']->nombre ?? 'Equipo' }}">
                            @foreach ($lado['jugadores'] as $jugador)
                                <option value="{{ $jugador->id }}"
                                        @selected((int) old('jugador_partido_id', $partido->jugador_partido_id) === $jugador->id)>
                                    {{ $jugador->nombre_completo }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <p class="figura-nota">Aparecera en la pagina publica entre los mejores de la jornada.</p>
            </div>
            </div>{{-- fin de bloque-normal --}}

            @if (session('aviso_doble_cobro'))
                <label style="display:flex; align-items:center; gap:6px; font-weight:normal; margin:14px 0;">
                    <input type="checkbox" name="confirmado" value="1" required>
                    Entiendo que se generara un cargo adicional y quiero continuar
                </label>
            @endif

            <div style="margin-top:16px;">
                <button class="btn" type="submit">Guardar resultado</button>
                <a class="btn btn-secondary" href="{{ route('partidos.index', ['torneo_id' => $partido->torneo_id]) }}">Cancelar</a>
            </div>
        </form>
    </div>

    <style>
        .tabla-registro .celda-cantidad {
            width: 70px;
            margin-bottom: 0;
            text-align: center;
        }
        .walkover-interruptor {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            padding: 10px 12px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            background: var(--gris-claro);
            cursor: pointer;
        }
        .walkover-datos {
            margin-top: 12px;
            padding: 14px 16px;
            border: 1px solid var(--gris-borde);
            border-left: 3px solid var(--rojo);
            border-radius: 6px;
            background: #fdf6f5;
        }
        .walkover-marcador { display: flex; align-items: center; gap: 8px; }
        .walkover-marcador input { width: 80px; text-align: center; margin-bottom: 0; }
        .walkover-marcador span { font-weight: 700; color: var(--azul-oscuro); }
        .walkover-nota { margin: 8px 0 0; font-size: 12px; color: #666; }

        .figura {
            margin-top: 16px;
            padding: 14px 16px;
            background: #fffaf0;
            border: 1px solid var(--gris-borde);
            border-left: 3px solid var(--naranja);
            border-radius: 6px;
        }
        .figura label { display: block; font-weight: 600; margin-bottom: 6px; }
        .figura select { max-width: 420px; margin-bottom: 0; }
        .figura-nota { margin: 6px 0 0; font-size: 12px; color: #666; }

        .marcador-resumen {
            margin-top: 18px;
            padding: 12px 16px;
            background: var(--gris-claro);
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            color: var(--azul-oscuro);
        }
        .marcador-resumen #marcador { font-size: 20px; }
    </style>
    <script>
        /**
         * El marcador se arma con los goles cargados: el autogol de un
         * jugador suma para el equipo contrario.
         */
        function recalcularMarcador() {
            let local = 0;
            let visitante = 0;

            document.querySelectorAll('.cuenta-gol').forEach(function (campo) {
                const cantidad = parseInt(campo.value || 0, 10);
                if (!cantidad) return;

                const esLocal = campo.dataset.equipo === 'local';
                const esAutogol = campo.dataset.tipo === '{{ \App\Models\IncidenciaPartido::TIPO_AUTOGOL }}';

                if (esLocal === !esAutogol) {
                    local += cantidad;
                } else {
                    visitante += cantidad;
                }
            });

            document.getElementById('marcador').textContent = local + ' - ' + visitante;
        }

        document.querySelectorAll('.celda-cantidad').forEach(function (campo) {
            campo.addEventListener('input', recalcularMarcador);
        });
        recalcularMarcador();

        // Walkover: se muestra un bloque u otro, nunca los dos. Los campos
        // del bloque oculto se deshabilitan para que no viajen en el envio
        // y no se mezclen los dos caminos.
        (function () {
            const casilla = document.getElementById('campo-walkover');
            const bloqueWalkover = document.getElementById('bloque-walkover');
            const bloqueNormal = document.getElementById('bloque-normal');

            if (!casilla) {
                return;
            }

            const avisoNormal = document.getElementById('aviso-normal');

            function alternar() {
                const esWalkover = casilla.checked;

                bloqueWalkover.hidden = !esWalkover;
                bloqueNormal.hidden = esWalkover;

                if (avisoNormal) {
                    avisoNormal.hidden = esWalkover;
                }

                bloqueWalkover.querySelectorAll('input, select').forEach(function (campo) {
                    campo.disabled = !esWalkover;
                });
                bloqueNormal.querySelectorAll('input, select').forEach(function (campo) {
                    campo.disabled = esWalkover;
                });
            }

            casilla.addEventListener('change', alternar);
            alternar();
        })();
    </script>
@endsection
