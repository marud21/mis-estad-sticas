<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { color: #0b2545; font-size: 18px; margin-bottom: 2px; }
        h2 { color: #0b2545; font-size: 14px; border-bottom: 1px solid #0b2545; padding-bottom: 4px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        th { background: #0b2545; color: #fff; text-align: left; padding: 6px; word-wrap: break-word; }
        td { padding: 6px; border-bottom: 1px solid #dde3ea; word-wrap: break-word; overflow-wrap: break-word; }
        .resumen { margin-top: 16px; }
        .resumen td { padding: 4px 6px; border: none; }
        .positivo { color: #1e7a3e; font-weight: bold; }
        .negativo { color: #b3261e; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Reporte de contabilidad</h1>
    <p>Periodo: {{ $etiquetaPeriodo }}</p>

    <h2>Cargos ({{ $cargos->count() }})</h2>
    <table>
        <thead><tr>
            <th style="width:15%;">Fecha</th>
            <th style="width:45%;">Socio</th>
            <th style="width:20%;">Tipo</th>
            <th style="width:20%;">Monto</th>
        </tr></thead>
        <tbody>
            @forelse ($cargos as $cargo)
                <tr>
                    <td>{{ $cargo->fecha_fmt }}</td>
                    <td>{{ $cargo->socio_nombre }}</td>
                    <td>{{ $cargo->tipo_nombre }}</td>
                    <td>${{ number_format($cargo->monto, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4">Sin cargos en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Pagos ({{ $pagos->count() }})</h2>
    <table>
        <thead><tr>
            <th style="width:13%;">Fecha</th>
            <th style="width:32%;">Socio</th>
            <th style="width:17%;">Tipo de pago</th>
            <th style="width:20%;">Equipo</th>
            <th style="width:18%;">Valor</th>
        </tr></thead>
        <tbody>
            @forelse ($pagos as $pago)
                <tr>
                    <td>{{ $pago->fecha_fmt }}</td>
                    <td>{{ $pago->socio_nombre }}</td>
                    <td>{{ ucfirst($pago->tipo) }}</td>
                    <td>{{ $pago->equipo_nombre ?? '-' }}</td>
                    <td>${{ number_format($pago->valor, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Sin pagos en este periodo.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="resumen">
        <tr><td><strong>Total cargos:</strong></td><td>${{ number_format($totalCargos, 0, ',', '.') }}</td></tr>
        <tr><td><strong>Total pagos (ingresos):</strong></td><td>${{ number_format($totalPagos, 0, ',', '.') }}</td></tr>
        <tr>
            <td><strong>Balance (pagos - cargos):</strong></td>
            <td class="{{ $neto >= 0 ? 'positivo' : 'negativo' }}">${{ number_format($neto, 0, ',', '.') }}</td>
        </tr>
    </table>
</body>
</html>
