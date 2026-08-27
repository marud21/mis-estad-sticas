<div class="grid-2">
    <div style="grid-column: 1 / -1;">
        <label>Titulo</label>
        <input type="text" name="titulo" value="{{ old('titulo', $noticia->titulo ?? '') }}" required>
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Contenido</label>
        <textarea name="contenido" rows="8" required>{{ old('contenido', $noticia->contenido ?? '') }}</textarea>
    </div>
    <div>
        <label>Fecha de publicacion</label>
        <input type="date" name="fecha_publicacion" value="{{ old('fecha_publicacion', optional($noticia->fecha_publicacion ?? null)->format('Y-m-d') ?? date('Y-m-d')) }}" required>
    </div>
    <div>
        <label>Estado</label>
        <select name="publicado">
            <option value="1" @selected(old('publicado', ($noticia->publicado ?? true) ? '1' : '0') == '1')>Publicada (visible en la pagina publica)</option>
            <option value="0" @selected(old('publicado', ($noticia->publicado ?? true) ? '1' : '0') == '0')>Borrador (no visible)</option>
        </select>
    </div>
    <div style="grid-column: 1 / -1;">
        <label>Imagen (opcional)</label>
        @if (($noticia->imagen_path ?? null))
            <div style="margin-bottom:10px;">
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($noticia->imagen_path) }}" alt="Imagen actual" style="max-width:100%; max-height:160px; height:auto; border:1px solid var(--gris-borde); border-radius:8px;">
            </div>
        @endif
        <input type="file" name="imagen" accept="image/*">
    </div>
</div>
