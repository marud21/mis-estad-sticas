@php
    $posiciones = ['Portero', 'Defensa', 'Mediocampista', 'Delantero'];
    $equipoActualId = $socio?->equipos->first()->id ?? null;
@endphp
<div class="grid-2">
    <div>
        <label>Nombre completo</label>
        <input type="text" name="nombre_completo" value="{{ old('nombre_completo', $socio->nombre_completo ?? '') }}" required>
    </div>
    <div>
        <label>Numero de documento</label>
        <input type="text" name="numero_documento" value="{{ old('numero_documento', $socio->numero_documento ?? '') }}" required>
    </div>
    <div>
        <label>Fecha de nacimiento</label>
        <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', optional($socio->fecha_nacimiento ?? null)->format('Y-m-d')) }}">
    </div>
    <div>
        <label>Fecha de ingreso</label>
        <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', optional($socio->fecha_ingreso ?? null)->format('Y-m-d')) }}">
    </div>
    <div>
        <label>Entidad de salud</label>
        <input type="text" name="entidad_salud" value="{{ old('entidad_salud', $socio->entidad_salud ?? '') }}">
    </div>
    <div>
        <label>Numero de celular</label>
        <input type="text" name="celular" value="{{ old('celular', $socio->celular ?? '') }}">
    </div>
    <div>
        <label>Tipo de sangre</label>
        <input type="text" name="tipo_sangre" placeholder="O+, A-, ..." value="{{ old('tipo_sangre', $socio->tipo_sangre ?? '') }}">
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Direccion de residencia</label>
        <input type="text" name="direccion_residencia" value="{{ old('direccion_residencia', $socio->direccion_residencia ?? '') }}">
    </div>
    <div>
        <label>Equipo</label>
        <select name="equipo_id">
            <option value="">-- Sin asignar --</option>
            @foreach ($equipos as $equipo)
                <option value="{{ $equipo->id }}" @selected(old('equipo_id', $equipoActualId) == $equipo->id)>{{ $equipo->nombre }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Posicion de juego</label>
        <select name="posicion_juego">
            <option value="">-- Selecciona --</option>
            @foreach ($posiciones as $posicion)
                <option value="{{ $posicion }}" @selected(old('posicion_juego', $socio->posicion_juego ?? '') === $posicion)>{{ $posicion }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label>Numero de camiseta</label>
        <input type="number" name="numero_camiseta" min="0" max="999" value="{{ old('numero_camiseta', $socio->numero_camiseta ?? '') }}">
    </div>
    <div>
        <label>Nivel de jugador</label>
        <select name="nivel_jugador">
            <option value="">-- Selecciona --</option>
            <option value="1" @selected(old('nivel_jugador', $socio->nivel_jugador ?? '') == 1)>1 - Bueno</option>
            <option value="2" @selected(old('nivel_jugador', $socio->nivel_jugador ?? '') == 2)>2 - Regular</option>
            <option value="3" @selected(old('nivel_jugador', $socio->nivel_jugador ?? '') == 3)>3 - Malo</option>
        </select>
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Foto del socio</label>
        @if (($socio->foto_path ?? null))
            <div style="margin-bottom:10px;">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($socio->foto_path) }}" alt="Foto actual" style="height:96px; width:96px; object-fit:cover; border:1px solid var(--gris-borde); border-radius:8px;">
            </div>
        @endif
        <input type="file" name="foto" accept="image/*">
        <p style="font-size:12px; color:#666; margin-top:-10px;">Puedes seleccionar una imagen de la galeria/archivos o tomar una foto con la camara del dispositivo.</p>
    </div>
    @if ($socio)
        <div>
            <label>Estado</label>
            <select name="estado">
                <option value="activo" @selected($socio->estado === 'activo')>Activo</option>
                <option value="suspendido" @selected($socio->estado === 'suspendido')>Suspendido</option>
                <option value="retirado" @selected($socio->estado === 'retirado')>Retirado</option>
            </select>
        </div>
    @endif
</div>
