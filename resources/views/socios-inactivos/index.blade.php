@extends('layouts.app')

@section('title', 'Socios sin pagos recientes')

@section('content')
    @php
        $puedeRetirar = \App\Support\Permisos::puede(auth()->user(), 'socios.estado');
        $limite = \App\Services\SocioInactivoService::MESES_LIMITE;
        // Los conteos salen del resumen y no de la pagina: lo que se ve en
        // pantalla son 25 filas, no el total.
        $conPagoFuturo = $resumen['con_fecha_adelantada'];
        $nuncaHanPagado = $resumen['nunca_han_pagado'];
    @endphp

    <x-breadcrumbs :items="['Socios' => route('socios.index'), 'Sin pagos recientes' => null]" />

    {{--
        Activar los avisos aqui, con la sesion abierta, es lo que deja el
        navegador marcado como de la administracion. Los avisos internos
        nombran socios y deudas, asi que solo salen hacia esos navegadores
        y nunca hacia los visitantes de la pagina publica.
    --}}
    @include('partials.avisos-push', [
        'avisoTitulo' => 'Avisarme de los socios sin pagos',
        'avisoTexto' => 'Activa los avisos y cada lunes te llega una notificacion con cuantos socios hay por revisar.',
    ])

    <div class="card">
        <h1>Socios sin pagos recientes</h1>
        <p style="color:#666; font-size:14px;">
            Socios en estado <strong>Activo</strong> o <strong>Sostenimiento</strong> que llevan
            <strong>{{ $meses }} meses o mas</strong> sin hacerle ningun pago a la corporacion. Al retirado y al
            excluido ya se les tomo una decision, asi que no se incluyen.
            Esta pantalla <strong>no retira a nadie</strong>: arma la lista para revisarla y decidir uno por uno.
        </p>

        <form id="filtros-inactivos" method="GET" action="{{ route('socios-inactivos.index') }}">
            <input type="text" name="q" placeholder="Buscar por nombre o documento..."
                   value="{{ $filtros['q'] }}">

            <select name="meses" onchange="this.form.submit()" title="Desde cuantos meses sin pagar">
                @foreach ([3, 4, 6, 9, 12, 24] as $opcion)
                    <option value="{{ $opcion }}" @selected((int) $meses === $opcion)>
                        Desde {{ $opcion }} meses{{ $opcion === $limite ? ' (lo normal)' : '' }}
                    </option>
                @endforeach
            </select>

            <select name="estado" onchange="this.form.submit()">
                <option value="">-- Activos y en sostenimiento --</option>
                @foreach (\App\Services\SocioInactivoService::ESTADOS_VIGILADOS as $unEstado)
                    <option value="{{ $unEstado }}" @selected($filtros['estado'] === $unEstado)>
                        Solo {{ $estados[$unEstado] }}
                    </option>
                @endforeach
            </select>

            <select name="equipo_id" onchange="this.form.submit()">
                <option value="">-- Todos los equipos --</option>
                <option value="{{ \App\Services\SocioInactivoService::SIN_EQUIPO }}"
                        @selected($filtros['equipo_id'] === \App\Services\SocioInactivoService::SIN_EQUIPO)>
                    Sin equipo
                </option>
                @foreach ($equipos as $equipo)
                    <option value="{{ $equipo->id }}" @selected($filtros['equipo_id'] === (string) $equipo->id)>
                        {{ $equipo->nombre }}{{ $equipo->categoria ? ' ('.$equipo->categoria.')' : '' }}
                    </option>
                @endforeach
            </select>

            <label>
                <input type="checkbox" name="solo_sin_pagos" value="1"
                       @checked($filtros['solo_sin_pagos']) onchange="this.form.submit()">
                Solo los que nunca han pagado
            </label>

            <label>
                <input type="checkbox" name="con_fecha_adelantada" value="1"
                       @checked($filtros['con_fecha_adelantada']) onchange="this.form.submit()">
                Solo con fecha adelantada
            </label>

            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if ($hayFiltros || (int) $meses !== $limite)
                <a class="btn btn-secondary" href="{{ route('socios-inactivos.index') }}">Limpiar</a>
            @endif
        </form>

        @if ($socios->isEmpty())
            @if ($hayFiltros)
                <p>
                    <strong>Ningun socio cumple con ese filtro.</strong>
                    Con el corte en {{ $meses }} meses hay {{ $totalSinFiltrar }} para revisar en total:
                    <a href="{{ route('socios-inactivos.index', ['meses' => $meses]) }}">verlos todos</a>.
                </p>
            @else
                <p><strong>Ningun socio activo o en sostenimiento lleva {{ $meses }} meses sin pagar.</strong></p>
            @endif
        @else
            <div class="alert">
                @if ($hayFiltros)
                    Se muestran <strong>{{ $resumen['total'] }}</strong> de los
                    <strong>{{ $totalSinFiltrar }}</strong> socio(s) que llevan {{ $meses }} meses o mas sin pagar.
                    Los filtrados deben
                    <strong>${{ number_format($resumen['deuda'], 0, ',', '.') }}</strong>.
                @else
                    Hay <strong>{{ $resumen['total'] }}</strong> socio(s) para revisar, y entre todos deben
                    <strong>${{ number_format($resumen['deuda'], 0, ',', '.') }}</strong>.
                @endif
                La lista va del que mas lleva sin pagar al que menos.
                @if ($socios->hasPages())
                    Esta viendo del {{ $socios->firstItem() }} al {{ $socios->lastItem() }}.
                @endif
            </div>

            @if ($nuncaHanPagado > 0)
                <p style="color:#666; font-size:14px;">
                    De esos, <strong>{{ $nuncaHanPagado }}</strong> no registran ningun pago desde que
                    entraron. A ellos se les cuenta el tiempo desde su fecha de ingreso.
                </p>
            @endif

            @if ($conPagoFuturo > 0)
                <div class="alert">
                    <strong>Ojo con {{ $conPagoFuturo }} de ellos:</strong> tienen un pago registrado con
                    fecha posterior a hoy, que casi siempre es un error de digitacion. Ese pago no se cuenta como
                    hecho, por eso aparecen aqui aunque en otras pantallas se vean al dia. Van marcados con
                    <span class="marca-futuro">fecha adelantada</span>.
                </div>
            @endif

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th>Documento</th>
                            <th>Equipo</th>
                            <th>Estado</th>
                            <th>Ultimo pago</th>
                            <th class="text-right">Lleva sin pagar</th>
                            <th class="text-right">Deuda</th>
                            <th>Contacto</th>
                            @if ($puedeRetirar)
                                <th></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($socios as $fila)
                            <tr>
                                <td>
                                    <a href="{{ route('socios.show', $fila['id']) }}">{{ $fila['nombre'] }}</a>
                                    @if ($fila['pago_futuro'])
                                        <span class="marca-futuro"
                                              title="Tiene un pago fechado el {{ $fila['pago_futuro']->format('d/m/Y') }}">
                                            fecha adelantada
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $fila['documento'] }}</td>
                                <td>{{ $fila['equipo'] ?? '--' }}</td>
                                <td><span class="badge badge-{{ $fila['estado'] }}">{{ $fila['estado_etiqueta'] }}</span></td>
                                <td>
                                    @if ($fila['nunca_ha_pagado'])
                                        <span class="sin-pagos">Nunca ha pagado</span>
                                        <br><small style="color:#888;">desde {{ $fila['desde']->format('d/m/Y') }}</small>
                                    @else
                                        {{ $fila['ultimo_pago']->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="text-right">
                                    {{ $fila['meses_sin_pagar'] }} {{ $fila['meses_sin_pagar'] === 1 ? 'mes' : 'meses' }}
                                </td>
                                <td class="text-right {{ $fila['deuda'] > 0 ? 'deuda-positiva' : '' }}">
                                    ${{ number_format($fila['deuda'], 0, ',', '.') }}
                                </td>
                                <td>
                                    @if ($fila['celular'])
                                        {{ $fila['celular'] }}
                                    @else
                                        <span style="color:#999;">sin celular</span>
                                    @endif
                                </td>
                                @if ($puedeRetirar)
                                    <td>
                                        <form action="{{ route('socios.estado', $fila['id']) }}" method="POST"
                                              onsubmit="return confirm('¿Retirar a {{ addslashes($fila['nombre']) }}? Deja de pertenecer al club y sale de las planillas. Se puede volver a activar despues.');"
                                              style="margin:0;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="estado" value="{{ \App\Models\Socio::ESTADO_RETIRADO }}">
                                            <button class="btn btn-sm btn-danger" type="submit">Retirar</button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- appends() conserva los filtros al cambiar de pagina. --}}
            {{ $socios->appends(request()->query())->links() }}
        @endif
    </div>

    <style>
        /*
         * Misma maquetacion que el filtro de socios, y por la misma razon:
         * fuera de atributos "style" para que la consulta de pantalla
         * angosta pueda sobreescribirla, porque un estilo en linea le gana
         * a la hoja y dejaba los controles apretados en el telefono.
         */
        #filtros-inactivos {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        #filtros-inactivos > input,
        #filtros-inactivos > select { margin-bottom: 0; }
        #filtros-inactivos > select { width: auto; }
        #filtros-inactivos > label {
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: normal;
            white-space: nowrap;
        }

        @media (max-width: 560px) {
            #filtros-inactivos { flex-direction: column; align-items: stretch; }
            #filtros-inactivos > * { width: 100%; max-width: 100%; }
            #filtros-inactivos > label { white-space: normal; }
        }

        .marca-futuro {
            display: inline-block;
            background: #fff3cd;
            border: 1px solid #e0c060;
            color: #7a5c00;
            border-radius: 3px;
            padding: 1px 6px;
            font-size: 11px;
            white-space: nowrap;
        }

        .sin-pagos {
            color: #a31515;
            font-weight: bold;
        }
    </style>
@endsection
