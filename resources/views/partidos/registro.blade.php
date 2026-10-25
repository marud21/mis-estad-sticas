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

        <div class="alert">
            El marcador se calcula solo con los goles que cargues (un autogol suma al rival).
            Cada tarjeta le genera al socio su cargo correspondiente, con el mismo monto del cobro de tarjetas.
            Si vuelves a registrar este partido, se reemplaza lo anterior.
        </div>

        <form action="{{ route('partidos.registro.guardar', $partido) }}" method="POST">
            @csrf

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
    </script>
@endsection
