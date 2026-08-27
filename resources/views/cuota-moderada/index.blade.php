@extends('layouts.app')

@section('title', 'Cuota moderada')

@section('content')
    <x-breadcrumbs :items="['Cuota moderada' => null]" />

    <div class="card">
        <h1>Cuota moderada (abono minimo)</h1>
        <p style="color:#666; font-size:14px;">
            La cuota moderada es un porcentaje de la deuda de cada socio, y se muestra como "Abono minimo" en la
            planilla de pagos. Para que un socio pueda ponerse al dia en varios pagos sin que la cuota baje
            cada vez que abona, el valor queda <strong>fijo</strong> desde que se calcula hasta el proximo
            recalculo manual (normalmente al iniciar un nuevo mes).
        </p>

        <div class="alert">
            @if ($ultimaFecha)
                Ultimo recalculo: <strong>{{ \Illuminate\Support\Carbon::parse($ultimaFecha)->format('d/m/Y') }}</strong>.
            @else
                Aun no se ha hecho ningun recalculo. Mientras tanto, la planilla de pagos usa el {{ rtrim(rtrim(number_format($porcentaje, 2), '0'), '.') }}% de la deuda actual de cada socio.
            @endif
        </div>

        <h3>Porcentaje de la cuota moderada</h3>
        <form action="{{ route('cuota-moderada.porcentaje') }}" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; max-width:320px;">
            @csrf
            @method('PUT')
            <div style="flex:1; min-width:120px;">
                <input type="number" name="porcentaje" step="0.01" min="1" max="100" value="{{ old('porcentaje', $porcentaje) }}" required style="margin-bottom:0;">
            </div>
            <button class="btn btn-secondary btn-sm" type="submit">Guardar porcentaje</button>
        </form>
        <p style="font-size:12px; color:#666; margin-top:6px;">
            Cambiar el porcentaje no modifica las cuotas ya fijadas: hay que darle a "Recalcular" para que se aplique.
        </p>

        <h3 style="margin-top:20px;">Recalculo</h3>
        <form action="{{ route('cuota-moderada.recalcular') }}" method="POST"
              onsubmit="return confirm('¿Recalcular la cuota moderada de todos los socios con deuda pendiente usando el {{ rtrim(rtrim(number_format($porcentaje, 2), '0'), '.') }}% actual?\n\nSe fijara ese porcentaje de la deuda de cada uno en este momento, y se mantendra asi (sin bajar en cada pago) hasta el proximo recalculo manual.');">
            @csrf
            <button class="btn" type="submit">Recalcular cuota moderada ahora ({{ rtrim(rtrim(number_format($porcentaje, 2), '0'), '.') }}%)</button>
        </form>

        <button type="button" class="btn-anio-toggle" id="btn-toggle-socios" onclick="alternarSocios()">
            <span class="flecha" id="flecha-socios">&#9660;</span> Socios con deuda pendiente
            <span style="font-weight:normal; color:#666; font-size:12px;">({{ $socios->total() }} socio(s))</span>
        </button>

        <div id="panel-socios">
            <div class="table-scroll" style="margin-top:12px;">
                <table>
                    <thead>
                        <tr>
                            <th>Socio</th>
                            <th>Documento</th>
                            <th class="text-right">Deuda actual</th>
                            <th class="text-right">Cuota moderada fija</th>
                            <th>Fijada el</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($socios as $socio)
                            <tr>
                                <td><a href="{{ route('socios.show', $socio) }}">{{ $socio->nombre_completo }}</a></td>
                                <td>{{ $socio->numero_documento }}</td>
                                <td class="text-right deuda-positiva">${{ number_format($socio->deuda_total, 0, ',', '.') }}</td>
                                <td class="text-right">
                                    @if ($socio->cuota_moderada !== null)
                                        ${{ number_format($socio->cuota_moderada_vigente, 0, ',', '.') }}
                                    @else
                                        <span style="color:#888;">Sin calcular (usa {{ rtrim(rtrim(number_format($porcentaje, 2), '0'), '.') }}% actual: ${{ number_format($socio->cuota_moderada_vigente, 0, ',', '.') }})</span>
                                    @endif
                                </td>
                                <td>{{ $socio->cuota_moderada_fecha?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No hay socios con deuda pendiente.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $socios->links() }}
        </div>
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
            margin-top: 20px;
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
    <script>
        function alternarSocios() {
            document.getElementById('panel-socios').classList.toggle('oculto');
            document.getElementById('btn-toggle-socios').classList.toggle('colapsado');
        }
    </script>
@endsection
