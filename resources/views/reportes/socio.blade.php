<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { color: #0b2545; font-size: 18px; margin-bottom: 4px; }
        h2 { color: #0b2545; font-size: 14px; border-bottom: 1px solid #0b2545; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th { background: #0b2545; color: #fff; text-align: left; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #dde3ea; }
        .info p { margin: 2px 0; }
        .deuda { font-weight: bold; color: #b3261e; }
    </style>
</head>
<body>
    <h1>Reporte de socio: {{ $socio->nombre_completo }}</h1>
    <div class="info">
        <p><strong>Documento:</strong> {{ $socio->numero_documento }}</p>
        <p><strong>Fecha de nacimiento:</strong> {{ optional($socio->fecha_nacimiento)->format('d/m/Y') ?? 'Sin registrar' }}</p>
        <p><strong>Entidad de salud:</strong> {{ $socio->entidad_salud }}</p>
        <p><strong>Celular:</strong> {{ $socio->celular }}</p>
        <p><strong>Tipo de sangre:</strong> {{ $socio->tipo_sangre }}</p>
        <p><strong>Direccion:</strong> {{ $socio->direccion_residencia }}</p>
        <p><strong>Posicion:</strong> {{ $socio->posicion_juego }}</p>
        <p><strong>Nivel:</strong> {{ ['1' => 'Bueno', '2' => 'Regular', '3' => 'Malo'][$socio->nivel_jugador] }}</p>
        <p><strong>Estado:</strong> {{ ucfirst($socio->estado) }}</p>
        <p><strong>Equipos:</strong> {{ $socio->equipos->pluck('nombre')->join(', ') ?: '-' }}</p>
    </div>

    <h2>Cargos</h2>
    <table>
        <thead><tr><th>Tipo</th><th>Monto</th><th>Fecha</th><th>Descripcion</th></tr></thead>
        <tbody>
            @forelse ($socio->cargos as $cargo)
                <tr>
                    <td>{{ $cargo->tipoCargo->nombre }}</td>
                    <td>${{ number_format($cargo->monto, 0, ',', '.') }}</td>
                    <td>{{ $cargo->fecha->format('d/m/Y') }}</td>
                    <td>{{ $cargo->descripcion }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin cargos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Pagos</h2>
    <table>
        <thead><tr><th>Valor</th><th>Fecha</th><th>Tipo</th><th>Abona a</th></tr></thead>
        <tbody>
            @forelse ($socio->pagos as $pago)
                <tr>
                    <td>${{ number_format($pago->valor, 0, ',', '.') }}</td>
                    <td>{{ $pago->fecha->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($pago->tipo) }}</td>
                    <td>{{ $pago->cargo->tipoCargo->nombre ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin pagos.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Resumen</h2>
    <p>Total cargos: ${{ number_format($socio->total_cargos, 0, ',', '.') }}</p>
    <p>Total pagos: ${{ number_format($socio->total_pagos, 0, ',', '.') }}</p>
    <p class="deuda">Deuda total: ${{ number_format($socio->deuda_total, 0, ',', '.') }}</p>
</body>
</html>
