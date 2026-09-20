@extends('layouts.app')

@section('title', 'Socios')

@section('content')
    <x-breadcrumbs :items="['Socios' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Socios</h1>
            <a href="{{ route('socios.create') }}" class="btn">+ Nuevo socio</a>
        </div>

        <form id="filtros-socios" action="{{ route('socios.index') }}" method="GET">
            <input type="text" name="q" placeholder="Buscar por nombre o documento..." value="{{ request('q') }}">
            <select name="estado" onchange="this.form.submit()">
                <option value="">-- Todos los estados --</option>
                @foreach (\App\Models\Socio::ESTADOS as $unEstado)
                    <option value="{{ $unEstado }}" @selected($estado === $unEstado)>{{ ucfirst($unEstado) }}</option>
                @endforeach
            </select>
            <select name="carnet" onchange="this.form.submit()">
                <option value="">-- Carnet: todos --</option>
                @foreach (\App\Models\Socio::CARNETS as $valorCarnet => $etiquetaCarnet)
                    <option value="{{ $valorCarnet }}" @selected($carnet === $valorCarnet)>Carnet: {{ $etiquetaCarnet }}</option>
                @endforeach
            </select>
            <label>
                <input type="checkbox" name="multi_equipo" value="1" @checked(request('multi_equipo')) onchange="this.form.submit()">
                Solo con mas de un equipo
            </label>
            <button class="btn btn-secondary" type="submit">Buscar</button>
            @if (request('q') || request('multi_equipo') || $estado !== '' || $carnet !== '')
                <a class="btn btn-secondary" href="{{ route('socios.index') }}">Limpiar</a>
            @endif
        </form>

        <p style="color:#666; font-size:13px; margin-top:-8px;">
            {{ number_format($socios->total(), 0, ',', '.') }} socio(s)
            @if ($estado !== '')
                en estado <strong>{{ ucfirst($estado) }}</strong>
            @endif
            @if ($carnet !== '')
                con carnet <strong>{{ \App\Models\Socio::CARNETS[$carnet] }}</strong>
            @endif
            <span style="margin-left:10px;">
                &middot; Carnet: ✅ Tiene &nbsp; ➖ Extraviado &nbsp; ❌ No tiene
            </span>
        </p>

        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Documento</th>
                    <th>Posicion</th>
                    <th>Nivel</th>
                    <th>Equipo</th>
                    <th>Estado</th>
                    <th style="text-align:center;">Carnet</th>
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
                        <td>{{ [1 => 'Bueno', 2 => 'Regular', 3 => 'Malo'][$socio->nivel_jugador] ?? 'Sin registrar' }}</td>
                        <td>{{ $socio->equipoActual()?->nombre ?? '-' }}</td>
                        <td><span class="badge badge-{{ $socio->estado }}">{{ ucfirst($socio->estado) }}</span></td>
                        <td style="text-align:center; font-size:16px;" title="Carnet: {{ $socio->carnet_etiqueta }}">{{ $socio->carnet_simbolo }}</td>
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
                    <tr><td colspan="9">No hay socios registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $socios->appends(request()->query())->links() }}

    <style>
        /*
         * La maquetacion del filtro va aqui y no en atributos "style" para
         * que la consulta de pantalla angosta pueda sobreescribirla: un
         * estilo en linea le gana a la hoja de estilos y dejaba los
         * controles centrados y apretados en el telefono.
         */
        #filtros-socios {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        #filtros-socios > input,
        #filtros-socios > select { margin-bottom: 0; }
        #filtros-socios > select { width: auto; }
        #filtros-socios > label {
            display: flex;
            align-items: center;
            gap: 4px;
            font-weight: normal;
            white-space: nowrap;
        }

        /*
         * En pantallas de telefono los filtros se apilan a lo ancho, para
         * que ningun control quede apretado contra el de al lado sin
         * depender de cuanto mida cada uno en el navegador del dispositivo.
         */
        @media (max-width: 560px) {
            #filtros-socios { flex-direction: column; align-items: stretch; }
            #filtros-socios > * { width: 100%; max-width: 100%; }
            #filtros-socios > label { white-space: normal; }
        }
    </style>
@endsection
