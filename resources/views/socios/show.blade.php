@extends('layouts.app')

@section('title', $socio->nombre_completo)

@section('content')
    <x-breadcrumbs :items="['Socios' => route('socios.index'), $socio->nombre_completo => null]" />
    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">{{ $socio->nombre_completo }}</h1>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('socios.reporte', $socio) }}">Exportar PDF</a>
                <a class="btn btn-secondary" href="{{ route('socios.whatsapp', $socio) }}" target="_blank">Enviar por WhatsApp</a>
                <a class="btn btn-secondary" href="{{ route('socios.edit', $socio) }}">Editar</a>
                <a class="btn btn-secondary" href="{{ route('socios.index') }}">Volver</a>
            </div>
        </div>

        @if ($socio->foto_path)
            <div style="margin-top:16px;">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($socio->foto_path) }}" alt="Foto de {{ $socio->nombre_completo }}" style="height:140px; width:140px; object-fit:cover; border:1px solid var(--gris-borde); border-radius:8px;">
            </div>
        @endif

        <div class="grid-2" style="margin-top: 16px;">
            <div>
                <p><strong>Documento:</strong> {{ $socio->numero_documento }}</p>
                <p><strong>Fecha de nacimiento:</strong> {{ optional($socio->fecha_nacimiento)->format('d/m/Y') ?? 'Sin registrar' }}</p>
                <p><strong>Entidad de salud:</strong> {{ $socio->entidad_salud ?? 'Sin registrar' }}</p>
                <p><strong>Celular:</strong> {{ $socio->celular ?? 'Sin registrar' }}</p>
            </div>
            <div>
                <p><strong>Tipo de sangre:</strong> {{ $socio->tipo_sangre ?? 'Sin registrar' }}</p>
                <p><strong>Direccion:</strong> {{ $socio->direccion_residencia ?? 'Sin registrar' }}</p>
                <p><strong>Posicion:</strong> {{ $socio->posicion_juego ?? 'Sin registrar' }}</p>
                <p><strong>Numero de camiseta:</strong> {{ $socio->numero_camiseta ?? 'Sin asignar' }}</p>
                <p><strong>Nivel:</strong> {{ [1 => 'Bueno', 2 => 'Regular', 3 => 'Malo'][$socio->nivel_jugador] ?? 'Sin registrar' }}</p>
            </div>
        </div>

        <p>
            <strong>Estado:</strong>
            <span class="badge badge-{{ $socio->estado }}">{{ ucfirst($socio->estado) }}</span>
            @if ($socio->fecha_cambio_estado)
                <span style="color:#666; font-size:13px;">(desde {{ $socio->fecha_cambio_estado->format('d/m/Y') }})</span>
            @endif
        </p>
        <form action="{{ route('socios.estado', $socio) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
            @csrf
            @method('PATCH')
            <select name="estado" style="width:auto; margin-bottom:0;">
                <option value="activo" @selected($socio->estado === 'activo')>Activo</option>
                <option value="suspendido" @selected($socio->estado === 'suspendido')>Suspendido</option>
                <option value="retirado" @selected($socio->estado === 'retirado')>Retirado</option>
            </select>
            <button class="btn btn-sm" type="submit">Cambiar estado</button>
        </form>

        <p><strong>Equipos:</strong> {{ $socio->equipos->pluck('nombre')->join(', ') ?: 'Sin equipo asignado' }}</p>

        <h2>Resumen financiero</h2>
        <p>Total cargos: ${{ number_format($socio->total_cargos, 0, ',', '.') }}</p>
        <p>Total pagos: ${{ number_format($socio->total_pagos, 0, ',', '.') }}</p>
        <p class="{{ $socio->deuda_total > 0 ? 'deuda-positiva' : 'deuda-cero' }}">
            Deuda total: ${{ number_format($socio->deuda_total, 0, ',', '.') }}
        </p>
    </div>

    <div class="card">
        <h2>Cargos</h2>
        <p style="font-size:12px; color:#666; margin-top:-8px;">Agrupados por año y, dentro de cada año, por torneo (el torneo se asigna segun el equipo del cargo en el momento en que se creo; si el equipo cambia de torneo despues, los cargos ya creados no se mueven).</p>

        @foreach ($cargosPorAnio as $anio => $cargosPorTorneoDelAnio)
            <button type="button" class="btn-anio-toggle {{ $loop->first ? '' : 'colapsado' }}" onclick="document.getElementById('cargos-anio-{{ $anio }}').classList.toggle('oculto'); this.classList.toggle('colapsado');">
                <span class="flecha">&#9660;</span> {{ $anio }}
                <span style="font-weight:normal; color:#666; font-size:12px;">({{ $cargosPorTorneoDelAnio->flatten()->count() }} cargo(s))</span>
            </button>
            <div id="cargos-anio-{{ $anio }}" class="{{ $loop->first ? '' : 'oculto' }}">
                @foreach ($cargosPorTorneoDelAnio as $nombreTorneo => $cargosDelTorneo)
                    <h4 style="font-size:13px; margin:0 0 6px; color:#555;">{{ $nombreTorneo }}</h4>
                    <table style="margin-bottom:18px;">
                        <thead>
                            <tr><th>Tipo</th><th>Equipo</th><th>Monto</th><th>Fecha</th><th>Descripcion</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($cargosDelTorneo as $cargo)
                                <tr>
                                    <td>{{ $cargo->tipoCargo->nombre }}</td>
                                    <td>{{ $cargo->equipo->nombre ?? '-' }}</td>
                                    <td>${{ number_format($cargo->monto, 0, ',', '.') }}</td>
                                    <td>{{ $cargo->fecha->format('d/m/Y') }}</td>
                                    <td>{{ $cargo->descripcion }}</td>
                                    <td class="actions">
                                        <a class="btn btn-sm btn-secondary" href="{{ route('socios.cargos.edit', [$socio, $cargo]) }}">Editar</a>
                                        <form action="{{ route('socios.cargos.destroy', [$socio, $cargo]) }}" method="POST" onsubmit="return confirm('¿Eliminar cargo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        @endforeach
        @if ($socio->cargos->isEmpty())
            <p>Sin cargos registrados.</p>
        @endif

        <h3>Agregar cargo</h3>
        <form action="{{ route('socios.cargos.store', $socio) }}" method="POST">
            @csrf
            <div class="grid-4">
                <div>
                    <label>Tipo de cargo</label>
                    <select name="tipo_cargo_id" required>
                        @foreach (\App\Models\TipoCargo::orderBy('nombre')->get() as $tipo)
                            <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Monto</label>
                    <input type="number" step="0.01" name="monto" required>
                </div>
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label>Equipo (opcional)</label>
                    <select name="equipo_id">
                        <option value="">-- General, sin equipo --</option>
                        @foreach ($socio->equipos as $equipo)
                            <option value="{{ $equipo->id }}">{{ $equipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Descripcion</label>
                    <input type="text" name="descripcion">
                </div>
            </div>
            <button class="btn" type="submit">Agregar cargo</button>
        </form>
    </div>

    <div class="card">
        <h2>Pagos</h2>
        <p style="font-size:12px; color:#666; margin-top:-8px;">Agrupados por año y, dentro de cada año, por torneo (segun el equipo que se asocio al pago en el momento en que se registro).</p>

        @foreach ($pagosPorAnio as $anio => $pagosPorTorneoDelAnio)
            <button type="button" class="btn-anio-toggle {{ $loop->first ? '' : 'colapsado' }}" onclick="document.getElementById('pagos-anio-{{ $anio }}').classList.toggle('oculto'); this.classList.toggle('colapsado');">
                <span class="flecha">&#9660;</span> {{ $anio }}
                <span style="font-weight:normal; color:#666; font-size:12px;">({{ $pagosPorTorneoDelAnio->flatten()->count() }} pago(s))</span>
            </button>
            <div id="pagos-anio-{{ $anio }}" class="{{ $loop->first ? '' : 'oculto' }}">
                @foreach ($pagosPorTorneoDelAnio as $nombreTorneo => $pagosDelTorneo)
                    <h4 style="font-size:13px; margin:0 0 6px; color:#555;">{{ $nombreTorneo }}</h4>
                    <table style="margin-bottom:18px;">
                        <thead>
                            <tr><th>Valor</th><th>Fecha</th><th>Tipo</th><th>Equipo</th><th>Abona a</th><th></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($pagosDelTorneo as $pago)
                                <tr>
                                    <td>${{ number_format($pago->valor, 0, ',', '.') }}</td>
                                    <td>{{ $pago->fecha->format('d/m/Y') }}</td>
                                    <td>{{ ucfirst($pago->tipo) }}</td>
                                    <td>{{ $pago->equipo->nombre ?? '-' }}</td>
                                    <td>{{ $pago->cargo->tipoCargo->nombre ?? '-' }}</td>
                                    <td class="actions">
                                        <a class="btn btn-sm btn-secondary" href="{{ route('pagos.recibo', $pago) }}" target="_blank">Imprimir recibo</a>
                                        <form action="{{ route('socios.pagos.destroy', [$socio, $pago]) }}" method="POST" onsubmit="return confirm('¿Eliminar pago?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>
        @endforeach
        @if ($socio->pagos->isEmpty())
            <p>Sin pagos registrados.</p>
        @endif

        <h3>Registrar pago</h3>
        <form action="{{ route('socios.pagos.store', $socio) }}" method="POST">
            @csrf
            <div class="grid-5">
                <div>
                    <label>Valor</label>
                    <input type="number" step="0.01" name="valor" required>
                </div>
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required>
                </div>
                <div>
                    <label>Tipo</label>
                    <select name="tipo" required>
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                    </select>
                </div>
                <div>
                    <label>Equipo asociado</label>
                    <select name="equipo_id">
                        <option value="">-- Ninguno --</option>
                        @foreach ($socio->equipos as $equipo)
                            <option value="{{ $equipo->id }}">{{ $equipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Cargo que abona</label>
                    <select name="cargo_id">
                        <option value="">-- Ninguno --</option>
                        @foreach ($socio->cargos as $cargo)
                            <option value="{{ $cargo->id }}">{{ $cargo->tipoCargo->nombre }} (${{ number_format($cargo->monto, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button class="btn" type="submit">Registrar pago</button>
        </form>
    </div>

    <style>
        .oculto { display: none; }
        .btn-anio-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            background: var(--gris-claro);
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 8px;
            font-size: 15px;
            font-weight: 600;
            color: var(--azul-oscuro);
            cursor: pointer;
            text-align: left;
        }
        .btn-anio-toggle:hover { background: #eaeef3; }
        .btn-anio-toggle .flecha {
            display: inline-block;
            font-size: 11px;
            transition: transform 0.15s ease;
        }
        .btn-anio-toggle.colapsado .flecha { transform: rotate(-90deg); }
    </style>

    @if (session('pago_recibo_id'))
        <script>
            if (confirm('Pago registrado. ¿Desea imprimir el recibo?')) {
                window.open('{{ route('pagos.recibo', session('pago_recibo_id')) }}', '_blank');
            }
        </script>
    @endif
@endsection
