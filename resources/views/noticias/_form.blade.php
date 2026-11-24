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
    <div>
        {{-- Una noticia nueva viene marcada: lo normal es que lo ultimo
             que se publica se quiera avisar. Se desmarca la que no
             amerite interrumpir a quien entra. --}}
        <label>Ventana de la portada</label>
        <select name="destacada">
            <option value="1" @selected(old('destacada', ($noticia->destacada ?? true) ? '1' : '0') == '1')>Si: salta al entrar a la portada</option>
            <option value="0" @selected(old('destacada', ($noticia->destacada ?? true) ? '1' : '0') == '0')>No: solo aparece en Noticias</option>
        </select>
        <p style="font-size:12px; color:#666; margin-top:-10px;">
            Entre las marcadas salta la mas reciente, y deja de saltar
            {{ \App\Models\Noticia::DIAS_DESTACADA }} dias despues de su fecha de publicacion.
            A cada visitante le sale una vez.
        </p>
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
    <div style="grid-column: 1 / -1;">
        <label>Archivos adjuntos (opcional)</label>

        @if (($noticia->adjuntos ?? collect())->isNotEmpty())
            <table style="margin-bottom:10px;">
                <thead>
                    <tr><th>Archivo</th><th>Peso</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($noticia->adjuntos as $adjunto)
                        <tr>
                            <td>
                                {{ $adjunto->icono }} {{ $adjunto->nombre_original }}
                                @unless ($adjunto->se_puede_previsualizar)
                                    <br><small style="color:var(--naranja);">
                                        el publico no va a poder verlo: el navegador no muestra este tipo de archivo
                                    </small>
                                @endunless
                            </td>
                            <td>{{ $adjunto->tamano_legible }}</td>
                            <td class="actions">
                                <a class="btn btn-sm btn-secondary" href="{{ $adjunto->url }}" target="_blank" rel="noopener">Ver</a>
                                <button type="button" class="btn btn-sm btn-danger"
                                        onclick="eliminarAdjunto('{{ route('noticias-admin.adjuntos.destroy', [$noticia, $adjunto]) }}', '{{ addslashes($adjunto->nombre_original) }}')">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <input type="file" name="adjuntos[]" multiple
               accept=".doc,.docx,.xml,.pdf,.png,.jpg,.jpeg">
        <p style="font-size:12px; color:#666; margin-top:-10px;">
            Se permiten archivos .doc, .docx, .xml, .pdf, .png y .jpg: hasta 10 por noticia,
            maximo 15 MB cada uno y {{ \App\Http\Requests\NoticiaRequest::LIMITE_TOTAL_MB }} MB entre todos los que subas de una vez.
            Si necesitas subir mas, guarda y vuelve a editar la noticia: los nuevos se agregan a los que ya tenga.
            <br>
            <strong>Los adjuntos se ven en pantalla y no se pueden descargar.</strong>
            El navegador solo sabe mostrar <strong>.pdf, .png y .jpg</strong>; si subes un
            .doc o un .docx, el publico lo vera en la lista pero no podra abrirlo.
            Para documentos, lo mejor es subirlos en PDF.
        </p>
        @error('adjuntos')
            <p style="color:#b3261e; font-size:13px; margin-top:-4px;">{{ $message }}</p>
        @enderror
        @foreach ($errors->get('adjuntos.*') as $mensajes)
            <p style="color:#b3261e; font-size:13px; margin-top:-4px;">{{ $mensajes[0] }}</p>
        @endforeach
    </div>
</div>
