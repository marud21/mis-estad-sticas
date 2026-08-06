<div class="grid-2">
    <div style="grid-column: 1 / -1;">
        <label>Nombre</label>
        <input type="text" name="nombre" value="{{ old('nombre', $torneo->nombre ?? '') }}" required>
    </div>
    <div>
        <label>Fecha de inicio</label>
        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', optional($torneo->fecha_inicio ?? null)->format('Y-m-d')) }}">
    </div>
    <div>
        <label>Fecha de fin</label>
        <input type="date" name="fecha_fin" value="{{ old('fecha_fin', optional($torneo->fecha_fin ?? null)->format('Y-m-d')) }}">
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Descripcion</label>
        <textarea name="descripcion" rows="3">{{ old('descripcion', $torneo->descripcion ?? '') }}</textarea>
    </div>
</div>
