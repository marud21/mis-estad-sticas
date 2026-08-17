<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { color: #0b2545; font-size: 18px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #0b2545; color: #fff; text-align: left; padding: 6px; }
        td { padding: 6px; border-bottom: 1px solid #dde3ea; }
        .deuda { font-weight: bold; color: #b3261e; }
    </style>
</head>
<body>
    <h1>Reporte de equipo: {{ $equipo->nombre }}</h1>
    <p>
        @if ($equipo->categoria) {{ $equipo->categoria }} &middot; @endif
        {{ $equipo->descripcion }}
    </p>

    @php
        $totalCargosEquipo = 0;
        $totalPagosEquipo = 0;
    @endphp

    <table>
        <thead>
            <tr>
                <th>Nombre</th><th>Documento</th><th>Estado</th>
                <th>Total cargos</th><th>Total pagos</th><th>Deuda</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($socios as $socio)
                @php
                    $totalCargos = $socio->cargos->sum('monto');
                    $totalPagos = $socio->pagos->sum('valor');
                    $deuda = $totalCargos - $totalPagos;
                    $totalCargosEquipo += $totalCargos;
                    $totalPagosEquipo += $totalPagos;
                @endphp
                <tr>
                    <td>{{ $socio->nombre_completo }}</td>
                    <td>{{ $socio->numero_documento }}</td>
                    <td>{{ ucfirst($socio->estado) }}</td>
                    <td>${{ number_format($totalCargos, 0, ',', '.') }}</td>
                    <td>${{ number_format($totalPagos, 0, ',', '.') }}</td>
                    <td class="deuda">${{ number_format($deuda, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="font-weight:bold; background:#eaf0f7;">
                <td colspan="3">TOTAL GENERAL DEL EQUIPO</td>
                <td>${{ number_format($totalCargosEquipo, 0, ',', '.') }}</td>
                <td>${{ number_format($totalPagosEquipo, 0, ',', '.') }}</td>
                <td class="deuda">${{ number_format($totalCargosEquipo - $totalPagosEquipo, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
