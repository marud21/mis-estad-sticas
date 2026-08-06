<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Planilla de Juego</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: DejaVu Sans, sans-serif;
  font-size: 10px;
  color: #111;
  padding: 8px 10px;
}

/* ── TABLA ENCABEZADO ───────────────────────────────────────────────────── */
table.hdr {
  width: 100%;
  border-collapse: collapse;
  border: 1.5px solid #333;
  margin-bottom: 0;
}
table.hdr td {
  border: 0.75px solid #555;
  vertical-align: middle;
  padding: 2px 5px;
}
.hdr-title {
  font-size: 11px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: #d6ead6;
  padding: 4px 5px;
}
.hdr-label {
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  font-size: 7.5px;
  background: #b8d9a0;
  width: 68px;
}
.hdr-val {
  font-size: 8px;
  font-weight: bold;
}
.hdr-logo {
  text-align: center;
  vertical-align: middle;
  width: 62px;
}
.hdr-logo img {
  width: 56px;
  height: 56px;
  object-fit: contain;
}

/* ── TABLA PRINCIPAL (jugadores) ────────────────────────────────────────── */
table.main {
  width: 100%;
  border-collapse: collapse;
  border: 1.5px solid #333;
  margin-top: 2px;
}
table.main td,
table.main th {
  border: 0.75px solid #555;
  padding: 0 3px;
  vertical-align: middle;
}

.th-team {
  font-size: 11px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 1px;
  background: #5a9a3a;
  color: #fff;
  padding: 4px 3px;
}

.th-col {
  font-size: 7.5px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  background: #b8d9a0;
  height: 16px;
}
.th-col-name {
  font-size: 7.5px;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  background: #b8d9a0;
}

.col-num  { width: 20px;  text-align: center; }
.col-name { width: 166px; overflow: hidden;    }
.col-a    { width: 20px;  text-align: center; }
.col-r    { width: 20px;  text-align: center; }
.col-gol  { width: 60px;  text-align: center; }

.sep { border-left: 2px solid #333 !important; }

.tr-player td {
  font-size: 10px;
  height: 28px;
}
.tr-even td { background: #eaf6e0; }
.tr-odd  td { background: #fff;    }

.td-result-label {
  font-size: 8px;
  font-weight: bold;
  text-transform: uppercase;
  text-align: center;
  background: #b8d9a0;
  height: 26px;
}
.td-result-blank { background: #fff; height: 26px; }

.td-arb {
  font-size: 8px;
  font-weight: bold;
  text-transform: uppercase;
  background: #d6ead6;
  padding: 2px 6px;
  height: 26px;
}
</style>
</head>
<body>

@php
    $nombreSistema = \App\Models\Configuracion::obtener(\App\Models\Configuracion::NOMBRE_SISTEMA, \App\Models\Configuracion::NOMBRE_SISTEMA_DEFECTO);
    $logoPath = \App\Models\Configuracion::obtener(\App\Models\Configuracion::LOGO_PATH);
    $logoAbsoluto = $logoPath ? \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath) : null;
@endphp

{{-- ══ ENCABEZADO ══════════════════════════════════════════════════════════════ --}}
<table class="hdr">
  <tr>
    <td colspan="4" class="hdr-title">
      {{ $nombreSistema }} @if (!empty($datosPartido['torneo'])) &mdash; {{ $datosPartido['torneo'] }} @endif
    </td>
    <td class="hdr-logo" rowspan="4">
      @if ($logoAbsoluto && file_exists($logoAbsoluto))
        <img src="{{ $logoAbsoluto }}" alt="Logo">
      @endif
    </td>
  </tr>
  <tr>
    <td class="hdr-label">Jornada</td>
    <td class="hdr-val">{{ $datosPartido['jornada'] ?? '' }}</td>
    <td class="hdr-label">Cancha</td>
    <td class="hdr-val">{{ $datosPartido['cancha'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="hdr-label">Fecha</td>
    <td class="hdr-val">{{ !empty($datosPartido['fecha']) ? \Illuminate\Support\Carbon::parse($datosPartido['fecha'])->format('d-M-y') : '' }}</td>
    <td class="hdr-label">Hora</td>
    <td class="hdr-val">{{ $datosPartido['hora'] ?? '' }}</td>
  </tr>
  <tr>
    <td class="hdr-label">Arbitro</td>
    <td colspan="3" class="hdr-val">{{ $datosPartido['arbitro'] ?? '' }}</td>
  </tr>
</table>

{{-- ══ TABLA PRINCIPAL ════════════════════════════════════════════════════════ --}}
@php
  $minRows  = 23;
  $localPad = max(0, $minRows - $jugadoresLocal->count());
  $visitPad = max(0, $minRows - $jugadoresVisitante->count());
  $maxRows  = max($jugadoresLocal->count() + $localPad, $jugadoresVisitante->count() + $visitPad);
  $localAll = $jugadoresLocal->values()->concat(collect(array_fill(0, $localPad, null)));
  $visitAll = $jugadoresVisitante->values()->concat(collect(array_fill(0, $visitPad, null)));
@endphp

<table class="main">
  <tr>
    <td class="th-team" colspan="5">{{ $equipoLocal->nombre }}</td>
    <td class="th-team sep" colspan="5">{{ $equipoVisitante->nombre }}</td>
  </tr>

  <tr>
    <th class="th-col"     style="text-align:center">N&deg;</th>
    <th class="th-col-name"                         >Jugador</th>
    <th class="th-col"     style="text-align:center">A</th>
    <th class="th-col"     style="text-align:center">R</th>
    <th class="th-col"     style="text-align:center">Goles</th>
    <th class="th-col sep" style="text-align:center">N&deg;</th>
    <th class="th-col-name"                         >Jugador</th>
    <th class="th-col"     style="text-align:center">A</th>
    <th class="th-col"     style="text-align:center">R</th>
    <th class="th-col"     style="text-align:center">Goles</th>
  </tr>

  @for ($i = 0; $i < $maxRows; $i++)
  @php
    $h   = $localAll[$i] ?? null;
    $a   = $visitAll[$i] ?? null;
    $cls = $i % 2 === 0 ? 'tr-odd' : 'tr-even';
  @endphp
  <tr class="tr-player {{ $cls }}">
    <td class="col-num">{{ $h?->numero_camiseta ?? '' }}</td>
    <td class="col-name" style="padding-left:4px;white-space:nowrap;overflow:hidden">{{ $h?->nombre_completo ?? '' }}</td>
    <td class="col-a"> </td>
    <td class="col-r"> </td>
    <td class="col-gol"> </td>
    <td class="col-num sep">{{ $a?->numero_camiseta ?? '' }}</td>
    <td class="col-name" style="padding-left:4px;white-space:nowrap;overflow:hidden">{{ $a?->nombre_completo ?? '' }}</td>
    <td class="col-a"> </td>
    <td class="col-r"> </td>
    <td class="col-gol"> </td>
  </tr>
  @endfor

  <tr>
    <td class="td-result-label" colspan="3">Resultado</td>
    <td class="td-result-blank">&nbsp;</td>
    <td class="td-result-blank">&nbsp;</td>
    <td class="td-result-label sep" colspan="3">Resultado</td>
    <td class="td-result-blank">&nbsp;</td>
    <td class="td-result-blank">&nbsp;</td>
  </tr>

  <tr class="tr-arb">
    <td class="td-arb" colspan="10">Arbitro:</td>
  </tr>

</table>

</body>
</html>
