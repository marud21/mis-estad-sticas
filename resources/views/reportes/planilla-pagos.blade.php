<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; padding: 8px 10px; }

        table.hdr {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #333;
            margin-bottom: 10px;
        }
        table.hdr td {
            border: 0.75px solid #555;
            vertical-align: middle;
            padding: 2px 5px;
        }
        .hdr-title {
            font-size: 13px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #d6ead6;
            padding: 5px;
        }
        .hdr-label {
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            font-size: 8.5px;
            background: #b8d9a0;
            width: 90px;
        }
        .hdr-val { font-size: 9px; font-weight: bold; }
        .hdr-logo { text-align: center; vertical-align: middle; width: 70px; }
        .hdr-logo img { width: 60px; height: 60px; object-fit: contain; }

        table.principal { width: 100%; border-collapse: collapse; border: 1.5px solid #333; }
        th { background: #5a9a3a; color: #fff; text-align: left; padding: 6px; font-size: 11px; text-transform: uppercase; }
        td { padding: 8px 6px; border-bottom: 1px solid #dde3ea; vertical-align: middle; }
        tr:nth-child(even) td { background: #eaf6e0; }
        .deuda { font-weight: bold; color: #b3261e; }
        .abono { font-weight: bold; color: #0b2545; }
        .firma-linea { display: inline-block; width: 100%; border-bottom: 1px solid #333; height: 18px; }
        .fecha-generacion { color: #888; font-size: 10px; margin-top: 16px; }
    </style>
</head>
<body>

@php
    $nombreSistema = \App\Models\Configuracion::obtener(\App\Models\Configuracion::NOMBRE_SISTEMA, \App\Models\Configuracion::NOMBRE_SISTEMA_DEFECTO);
    $logoPath = \App\Models\Configuracion::obtener(\App\Models\Configuracion::LOGO_PATH);
    $logoAbsoluto = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath) : null;
@endphp

<table class="hdr">
    <tr>
        <td colspan="3" class="hdr-title">{{ $nombreSistema }} &mdash; Planilla de pagos</td>
        <td class="hdr-logo" rowspan="2">
            @if ($logoAbsoluto && file_exists($logoAbsoluto))
                <img src="{{ $logoAbsoluto }}" alt="Logo">
            @endif
        </td>
    </tr>
    <tr>
        <td class="hdr-label">Equipo</td>
        <td class="hdr-val">{{ $equipo->nombre }}</td>
        <td class="hdr-val">@if ($equipo->categoria) {{ $equipo->categoria }} @endif</td>
    </tr>
</table>

    <table class="principal">
        <thead>
            <tr>
                <th style="width:14%;">Documento</th>
                <th style="width:26%;">Nombre</th>
                <th style="width:14%;">Deuda total</th>
                <th style="width:16%;">Abono minimo (25%)</th>
                <th style="width:30%;">Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($socios as $socio)
                @php
                    $totalCargos = $socio->cargos->sum('monto');
                    $totalPagos = $socio->pagos->sum('valor');
                    $deuda = $totalCargos - $totalPagos;
                    $abonoMinimo = $deuda > 0 ? $deuda * 0.25 : 0;
                @endphp
                <tr>
                    <td>{{ $socio->numero_documento }}</td>
                    <td>{{ $socio->nombre_completo }}</td>
                    <td class="deuda">${{ number_format($deuda, 0, ',', '.') }}</td>
                    <td class="abono">${{ number_format($abonoMinimo, 0, ',', '.') }}</td>
                    <td><span class="firma-linea"></span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="fecha-generacion">Generado el {{ now()->format('d/m/Y') }}</p>
</body>
</html>
