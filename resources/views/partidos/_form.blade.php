@php($esJugado = old('estado', $partido->estado ?? \App\Models\Partido::ESTADO_PROGRAMADO) === \App\Models\Partido::ESTADO_JUGADO)

<div class="grid-2">
    <div>
        <label>Torneo</label>
        <select name="torneo_id" id="campo-torneo" required>
            @foreach ($torneos as $t)
                <option value="{{ $t->id }}" @selected(old('torneo_id', $partido->torneo_id ?? ($torneo->id ?? null)) == $t->id)>{{ $t->nombre }}</option>
            @endforeach
        </select>
        <p style="font-size:12px; color:#666; margin-top:-10px;">
            Solo se pueden enfrentar equipos inscritos en este torneo. Si falta alguno, asignale el torneo desde su ficha de equipo.
        </p>
    </div>
    <div>
        <label>Fase o jornada (opcional)</label>
        <input type="text" name="fase" maxlength="50" placeholder="Fecha 3, Semifinal..." value="{{ old('fase', $partido->fase ?? '') }}">
    </div>
    <div>
        <label>Equipo local</label>
        <select name="equipo_local_id" required>
            <option value="">-- Selecciona --</option>
            @foreach ($equipos as $e)
                <option value="{{ $e->id }}" @selected(old('equipo_local_id', $partido->equipo_local_id ?? null) == $e->id)>{{ $e->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Equipo visitante</label>
        <select name="equipo_visitante_id" required>
            <option value="">-- Selecciona --</option>
            @foreach ($equipos as $e)
                <option value="{{ $e->id }}" @selected(old('equipo_visitante_id', $partido->equipo_visitante_id ?? null) == $e->id)>{{ $e->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Fecha y hora</label>
        <input type="datetime-local" name="fecha_hora" required
               value="{{ old('fecha_hora', optional($partido->fecha_hora ?? null)->format('Y-m-d\TH:i') ?? date('Y-m-d\TH:i')) }}">
    </div>
    <div>
        <label>Lugar (opcional)</label>
        <input type="text" name="lugar" maxlength="150" placeholder="Cancha principal" value="{{ old('lugar', $partido->lugar ?? '') }}">
    </div>
    <div>
        <label>Arbitro (opcional)</label>
        <input type="text" name="arbitro" maxlength="150" value="{{ old('arbitro', $partido->arbitro ?? '') }}">
        <p style="font-size:12px; color:#666; margin-top:-10px;">
            Se imprime en la planilla de juego. Si lo dejas vacio, queda el espacio en blanco para llenarlo a mano.
        </p>
    </div>
    <div>
        <label>Estado</label>
        <select name="estado" id="campo-estado" required onchange="alternarMarcador()">
            @foreach (\App\Models\Partido::ESTADOS as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('estado', $partido->estado ?? \App\Models\Partido::ESTADO_PROGRAMADO) === $valor)>{{ $etiqueta }}</option>
            @endforeach
        </select>
        <p style="font-size:12px; color:#666; margin-top:-10px;">
            Solo los partidos marcados como "Jugado" con marcador cargado cuentan para la tabla de posiciones.
        </p>
    </div>
    <div id="bloque-marcador" class="{{ $esJugado ? '' : 'oculto' }}">
        <label>Marcador (local - visitante)</label>
        <div style="display:flex; gap:8px; align-items:center;">
            <input type="number" name="goles_local" min="0" max="255" style="margin-bottom:0;"
                   value="{{ old('goles_local', $partido->goles_local ?? '') }}">
            <span style="font-weight:700;">-</span>
            <input type="number" name="goles_visitante" min="0" max="255" style="margin-bottom:0;"
                   value="{{ old('goles_visitante', $partido->goles_visitante ?? '') }}">
        </div>
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Observaciones (opcional)</label>
        <textarea name="observaciones" rows="2" maxlength="1000">{{ old('observaciones', $partido->observaciones ?? '') }}</textarea>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-error">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<style>
    .oculto { display: none; }
</style>
<script>
    // El marcador solo tiene sentido si el partido ya se jugo.
    function alternarMarcador() {
        const jugado = document.getElementById('campo-estado').value === '{{ \App\Models\Partido::ESTADO_JUGADO }}';
        document.getElementById('bloque-marcador').classList.toggle('oculto', !jugado);
    }

    @if (($recargarAlCambiarTorneo ?? false))
        // Al programar un partido, cambiar de torneo recarga la pantalla para
        // traer los equipos inscritos en ese torneo.
        document.getElementById('campo-torneo').addEventListener('change', function () {
            window.location = '{{ route('partidos.create') }}?torneo_id=' + this.value;
        });
    @endif
</script>
