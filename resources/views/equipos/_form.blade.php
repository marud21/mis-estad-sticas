<div>
    <label>Nombre</label>
    <input type="text" name="nombre" value="{{ old('nombre', $equipo->nombre ?? '') }}" required>
</div>
<div>
    <label>Categoria</label>
    <input type="text" name="categoria" placeholder="Ej: Categoria +40 anos" value="{{ old('categoria', $equipo->categoria ?? '') }}">
</div>
<div>
    <label>Torneo</label>
    <select name="torneo_id">
        <option value="">-- Ninguno --</option>
        @foreach (\App\Models\Torneo::orderByDesc('fecha_inicio')->get() as $torneo)
            <option value="{{ $torneo->id }}" @selected(old('torneo_id', $equipo->torneo_id ?? '') == $torneo->id)>{{ $torneo->nombre }}</option>
        @endforeach
    </select>
</div>
<div>
    <label>Descripcion</label>
    <textarea name="descripcion" rows="3">{{ old('descripcion', $equipo->descripcion ?? '') }}</textarea>
</div>
