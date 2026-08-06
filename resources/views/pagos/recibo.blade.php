<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $pago->id }}</title>
    @include('partials.favicon')
    <style>
        @page { margin: 4mm; }
        body {
            width: 72mm;
            margin: 0 auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #000;
        }
        h1 { font-size: 14px; text-align: center; margin: 4px 0; }
        .center { text-align: center; }
        hr { border: none; border-top: 1px dashed #000; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        td.right { text-align: right; }
        .total { font-size: 14px; font-weight: bold; }
        .btn-imprimir {
            display: block;
            margin: 12px auto;
            padding: 8px 16px;
            font-size: 13px;
            cursor: pointer;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <h1>{{ strtoupper($nombreSistema) }}</h1>
    <p class="center">Recibo de pago</p>
    <hr>
    <table>
        <tr><td>Recibo N&deg;:</td><td class="right">{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
        <tr><td>Fecha:</td><td class="right">{{ $pago->fecha->format('d/m/Y') }}</td></tr>
        <tr><td>Socio:</td><td class="right">{{ $pago->socio->nombre_completo }}</td></tr>
        <tr><td>Documento:</td><td class="right">{{ $pago->socio->numero_documento }}</td></tr>
        @if ($pago->equipo)
            <tr><td>Equipo:</td><td class="right">{{ $pago->equipo->nombre }}</td></tr>
        @endif
        @if ($pago->cargo)
            <tr><td>Abona a:</td><td class="right">{{ $pago->cargo->tipoCargo->nombre }}</td></tr>
        @endif
        <tr><td>Tipo de pago:</td><td class="right">{{ ucfirst($pago->tipo) }}</td></tr>
    </table>
    <hr>
    <table>
        <tr class="total"><td>TOTAL PAGADO</td><td class="right">${{ number_format($pago->valor, 0, ',', '.') }}</td></tr>
    </table>
    <hr>
    <p class="center">Deuda total actual:<br>${{ number_format($pago->socio->deuda_total, 0, ',', '.') }}</p>
    <hr>
    <p class="center">Gracias por su pago</p>

    <button class="btn-imprimir no-print" onclick="window.print()">Imprimir</button>

    <div class="no-print">
        @include('partials.footer-firma')
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
