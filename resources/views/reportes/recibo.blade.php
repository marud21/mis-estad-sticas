<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1a1a1a; }
        h1 { color: #0b2545; font-size: 18px; text-align: center; margin-bottom: 4px; }
        p.subtitulo { text-align: center; color: #555; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        td { padding: 6px 4px; border-bottom: 1px solid #dde3ea; }
        td.right { text-align: right; }
        .total { font-size: 16px; font-weight: bold; color: #0b2545; }
        .center { text-align: center; }
        hr { border: none; border-top: 1px solid #dde3ea; margin: 14px 0; }
    </style>
</head>
<body>
    @php
        $nombreSistema = \App\Models\Configuracion::obtener(\App\Models\Configuracion::NOMBRE_SISTEMA, \App\Models\Configuracion::NOMBRE_SISTEMA_DEFECTO);
    @endphp
    <h1>{{ strtoupper($nombreSistema) }}</h1>
    <p class="subtitulo">Recibo de pago</p>

    <table>
        <tr><td>Recibo N&deg;</td><td class="right">{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td>Fecha</td><td class="right">{{ $pago->fecha->format('d/m/Y') }}</td></tr>
        <tr><td>Socio</td><td class="right">{{ $pago->socio->nombre_completo }}</td></tr>
        <tr><td>Documento</td><td class="right">{{ $pago->socio->numero_documento }}</td></tr>
        @if ($pago->equipo)
            <tr><td>Equipo</td><td class="right">{{ $pago->equipo->nombre }}</td></tr>
        @endif
        @if ($pago->cargo)
            <tr><td>Abona a</td><td class="right">{{ $pago->cargo->tipoCargo->nombre }}</td></tr>
        @endif
        <tr><td>Tipo de pago</td><td class="right">{{ ucfirst($pago->tipo) }}</td></tr>
        <tr class="total"><td>TOTAL PAGADO</td><td class="right">${{ number_format($pago->valor, 0, ',', '.') }}</td></tr>
    </table>

    <hr>
    <p class="center">Deuda total actual: <strong>${{ number_format($pago->socio->deuda_total, 0, ',', '.') }}</strong></p>
    <p class="center">Gracias por su pago</p>
</body>
</html>
