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

    // Numero de camiseta: lo que se escribio si el formulario ya rebotó
    // una vez (por el aviso de doble cobro, por ejemplo), y si no, lo que
    // el socio tiene hoy en su ficha.
    $numeroDe = function ($jugador) {
        $previo = old("jugadores.{$jugador->id}.numero");

        return $previo !== null ? $previo : $jugador->numero_camiseta;
    };

    // Carnet: igual, pero una casilla sin marcar no viaja en el envio, asi
    // que no se puede preguntar por ella sola. Se mira si el formulario ya
    // se habia mandado; solo entonces manda lo que venia marcado.
    $yaSeEnvio = old('jugadores') !== null;
    $carnetDe = function ($jugador) use ($yaSeEnvio) {
        return $yaSeEnvio
            ? (bool) old("jugadores.{$jugador->id}.carnet")
            : $jugador->carnet === \App\Models\Socio::CARNET_TIENE;
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
            <br>
            <strong>El numero de camiseta y el carnet se guardan en la ficha del socio</strong>, asi que
            puedes corregirlos aqui mismo mientras tienes al jugador enfrente.
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
                                    <th class="col-numero">#</th>
                                    <th>Jugador</th>
                                    <th class="text-center col-carnet">Carnet</th>
                                    <th class="text-center">Goles</th>
                                    <th class="text-center">Autogoles</th>
                                    <th class="text-center">Amarillas</th>
                                    <th class="text-center">Rojas</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lado['jugadores'] as $jugador)
                                    <tr>
                                        <td>
                                            <input type="number" min="1" max="99" placeholder="-"
                                                   class="celda-numero"
                                                   name="jugadores[{{ $jugador->id }}][numero]"
                                                   value="{{ $numeroDe($jugador) }}"
                                                   aria-label="Numero de camiseta de {{ $jugador->nombre_completo }}">
                                        </td>
                                        <td>
                                            {{ $jugador->nombre_completo }}
                                            <input type="hidden" name="jugadores[{{ $jugador->id }}][equipo_id]" value="{{ $equipo->id }}">
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" value="1"
                                                   name="jugadores[{{ $jugador->id }}][carnet]"
                                                   @checked($carnetDe($jugador))
                                                   aria-label="{{ $jugador->nombre_completo }} presento el carnet">
                                            @if ($jugador->carnet === \App\Models\Socio::CARNET_EXTRAVIADO)
                                                {{-- La casilla no puede decir "extraviado", asi que se
                                                     avisa aqui: dejarla sin marcar no le quita ese estado. --}}
                                                <div class="marca-extraviado">extraviado</div>
                                            @endif
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
        .tabla-registro .col-numero { width: 72px; }
        .tabla-registro .col-carnet { width: 64px; }
        .tabla-registro .celda-numero {
            width: 60px;
            margin-bottom: 0;
            text-align: center;
        }
        .marca-extraviado {
            font-size: 10px;
            color: var(--naranja);
            line-height: 1.2;
            margin-top: 2px;
        }

        /*
         * En el telefono la tabla se desliza de lado. Sin acotar el
         * nombre, el numero y la casilla del carnet quedaban en orillas
         * opuestas y habia que arrastrar para pasar de uno al otro, que
         * es justo lo que se queria evitar poniendolos aqui. Con el
         * nombre a dos renglones los tres caben juntos.
         */
        @media (max-width: 640px) {
            .tabla-registro td:nth-child(2),
            .tabla-registro th:nth-child(2) {
                max-width: 150px;
                white-space: normal;
            }
            .tabla-registro th, .tabla-registro td { padding: 8px 6px; }
            .tabla-registro .celda-numero { width: 52px; }
            .tabla-registro .celda-cantidad { width: 56px; }

            /* Sin las flechitas del campo numerico: en 52px se comen el
               espacio y un numero de dos cifras queda cortado. Aqui se
               escribe, no se sube de a uno. */
            .tabla-registro .celda-numero {
                -moz-appearance: textfield;
                appearance: textfield;
            }
            .tabla-registro .celda-numero::-webkit-outer-spin-button,
            .tabla-registro .celda-numero::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }
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
        .walkover-marcador span { font-weight: 700; color: var(--marca-oscura); }
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
            color: var(--marca-oscura);
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
