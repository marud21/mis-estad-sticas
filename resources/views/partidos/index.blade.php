@extends('layouts.app')

@section('title', 'Partidos')

@section('content')
    <x-breadcrumbs :items="['Partidos' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Partidos</h1>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('partidos.posiciones', ['torneo_id' => $torneo?->id]) }}">Ver posiciones</a>
                <a class="btn" href="{{ route('partidos.create', ['torneo_id' => $torneo?->id]) }}">+ Programar partido</a>
            </div>
        </div>

        @if ($torneos->isEmpty())
            <div class="alert">
                Primero necesitas crear un torneo y asignarle equipos.
                <a href="{{ route('torneos.create') }}">Crear torneo</a>.
            </div>
        @else
            <form id="filtros-partidos" action="{{ route('partidos.index') }}" method="GET">
                <select name="torneo_id" onchange="this.form.submit()">
                    @foreach ($torneos as $t)
                        <option value="{{ $t->id }}" @selected($torneo && $torneo->id === $t->id)>{{ $t->nombre }}</option>
                    @endforeach
                </select>
                <select name="estado" onchange="this.form.submit()">
                    <option value="">-- Todos los estados --</option>
                    @foreach (\App\Models\Partido::ESTADOS as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Filtrar</button>
            </form>

            <p style="color:#666; font-size:13px; margin-top:-8px;">
                {{ $partidos->total() }} partido(s)
                @if ($estado !== '')
                    en estado <strong>{{ \App\Models\Partido::ESTADOS[$estado] }}</strong>
                @endif
            </p>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Fase</th>
                            <th>Local</th>
                            <th class="text-center">Marcador</th>
                            <th>Visitante</th>
                            <th>Lugar</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($partidos as $partido)
                            <tr>
                                <td style="white-space:nowrap;">{{ $partido->fecha_hora->format('d/m/Y H:i') }}</td>
                                <td>{{ $partido->fase ?? '-' }}</td>
                                <td>{{ $partido->equipoLocal->nombre ?? '-' }}</td>
                                <td class="text-center" style="white-space:nowrap; font-weight:{{ $partido->tiene_resultado ? '700' : '400' }};">
                                    {{ $partido->marcador }}
                                </td>
                                <td>{{ $partido->equipoVisitante->nombre ?? '-' }}</td>
                                <td>{{ $partido->lugar ?? '-' }}</td>
                                <td><span class="badge badge-partido-{{ $partido->estado }}">{{ $partido->estado_etiqueta }}</span></td>
                                <td class="actions">
                                    <a class="btn btn-sm btn-secondary" href="{{ route('partidos.planilla', $partido) }}">Planilla</a>
                                    <a class="btn btn-sm" href="{{ route('partidos.registro', $partido) }}">{{ $partido->tiene_resultado ? 'Ver/corregir' : 'Registrar' }}</a>
                                    <a class="btn btn-sm btn-secondary" href="{{ route('partidos.edit', $partido) }}">Editar</a>
                                    <form action="{{ route('partidos.destroy', $partido) }}" method="POST" onsubmit="return confirm('¿Eliminar este partido?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8">Aun no hay partidos programados en este torneo.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $partidos->appends(request()->query())->links() }}
        @endif
    </div>

    <style>
        #filtros-partidos {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }
        #filtros-partidos > select { width: auto; margin-bottom: 0; }

        @media (max-width: 560px) {
            #filtros-partidos { flex-direction: column; align-items: stretch; }
            #filtros-partidos > * { width: 100%; max-width: 100%; }
        }
    </style>
@endsection
