<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $noticia->titulo }} - {{ $nombreSistema }}</title>
    @include('partials.favicon')
    <style>
        :root {
            --azul-oscuro: #0b2545;
            --azul-claro: #1d4e89;
            --blanco: #ffffff;
            --gris-claro: #f4f6f9;
            --gris-borde: #dde3ea;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gris-claro);
            color: #1a1a1a;
        }
        header.topbar {
            background: var(--azul-oscuro);
            color: var(--blanco);
            padding: 16px 24px;
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        header.topbar .brand { display: flex; align-items: center; gap: 10px; }
        header.topbar img {
            height: 28px;
            width: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: var(--blanco);
        }
        header.topbar a.volver {
            color: var(--blanco);
            text-decoration: none;
            font-size: 13px;
            font-weight: 400;
            opacity: 0.9;
        }
        header.topbar a.volver:hover { opacity: 1; text-decoration: underline; }
        main.container {
            max-width: 720px;
            margin: 32px auto;
            padding: 0 16px 48px;
        }
        .card {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 24px;
            overflow: hidden;
        }
        .card img {
            width: 100%;
            max-height: 360px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 20px;
            cursor: zoom-in;
        }
        h1 { color: var(--azul-oscuro); font-size: 24px; margin: 0 0 6px; }
        .fecha { font-size: 13px; color: #888; margin-bottom: 18px; }
        .contenido { font-size: 15px; line-height: 1.6; white-space: pre-line; }

        .modal-imagen {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.85);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 24px;
            cursor: zoom-out;
        }
        .modal-imagen.activo { display: flex; }
        .modal-imagen img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 6px;
            margin-bottom: 0;
            cursor: zoom-out;
        }
        .modal-imagen .cerrar {
            position: absolute;
            top: 16px;
            right: 24px;
            color: var(--blanco);
            font-size: 32px;
            font-weight: 300;
            line-height: 1;
            cursor: pointer;
            z-index: 2;
        }

        /* Visor de documentos: ocupa casi toda la pantalla y no se cierra
           al hacer clic dentro, para poder desplazarse por el archivo. */
        .modal-imagen iframe {
            width: 100%;
            flex: 1;
            background: var(--blanco);
            border: 0;
            border-radius: 0 0 6px 6px;
            cursor: default;
        }
        .modal-imagen .visor-doc {
            width: 100%;
            max-width: 960px;
            height: 100%;
            cursor: default;
            display: flex;
            flex-direction: column;
        }
        /* Safari en iOS no siempre dibuja un PDF dentro de un iframe, asi
           que el visor siempre ofrece abrirlo en una pestaña aparte. */
        .visor-barra {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            background: var(--azul-oscuro);
            color: var(--blanco);
            padding: 10px 14px;
            border-radius: 6px 6px 0 0;
            font-size: 13px;
        }
        .visor-barra a { color: var(--blanco); text-decoration: underline; white-space: nowrap; }

        .adjuntos { margin-top: 26px; border-top: 1px solid var(--gris-borde); padding-top: 18px; }
        .adjuntos h2 { font-size: 16px; color: var(--azul-oscuro); margin: 0 0 12px; }
        .adjuntos ul { list-style: none; margin: 0; padding: 0; }
        .adjuntos li {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            margin-bottom: 8px;
            background: var(--gris-claro);
        }
        .adjuntos .icono { font-size: 18px; }
        .adjuntos .nombre { flex: 1; min-width: 160px; font-size: 14px; word-break: break-word; }
        .adjuntos .peso { font-size: 12px; color: #888; white-space: nowrap; }
        .adjuntos .accion {
            font-size: 13px;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid var(--azul-claro);
            background: var(--blanco);
            color: var(--azul-claro);
            text-decoration: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .adjuntos .accion:hover { background: var(--azul-claro); color: var(--blanco); }
        .adjuntos .accion.principal { background: var(--azul-claro); color: var(--blanco); }
        .adjuntos .accion.principal:hover { background: var(--azul-oscuro); border-color: var(--azul-oscuro); }

        @media (max-width: 480px) {
            .adjuntos li { gap: 8px; }
            .adjuntos .accion { flex: 1; text-align: center; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo">
            @endif
            {{ $nombreSistema }} &mdash; Noticias
        </div>
        <a class="volver" href="{{ route('noticias-publicas.index') }}">&larr; Volver a noticias</a>
    </header>
    <main class="container">
        <div class="card">
            @if ($noticia->imagen_path)
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($noticia->imagen_path) }}" alt="{{ $noticia->titulo }}"
                     onclick="ampliarImagen('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($noticia->imagen_path) }}')">
            @endif
            <h1>{{ $noticia->titulo }}</h1>
            <div class="fecha">{{ $noticia->fecha_publicacion->format('d/m/Y') }}</div>
            <div class="contenido">{{ $noticia->contenido }}</div>

            @if ($noticia->adjuntos->isNotEmpty())
                <div class="adjuntos">
                    <h2>Archivos adjuntos ({{ $noticia->adjuntos->count() }})</h2>
                    <ul>
                        @foreach ($noticia->adjuntos as $adjunto)
                            <li>
                                <span class="icono">{{ $adjunto->icono }}</span>
                                <span class="nombre">{{ $adjunto->nombre_original }}</span>
                                <span class="peso">{{ $adjunto->tamano_legible }}</span>
                                @if ($adjunto->se_puede_previsualizar)
                                    <button type="button" class="accion principal"
                                            onclick="previsualizar('{{ $adjunto->url }}', @js($adjunto->nombre_original), {{ $adjunto->es_imagen ? 'true' : 'false' }})">
                                        Previsualizar
                                    </button>
                                @endif
                                <a class="accion" href="{{ $adjunto->url }}" download="{{ $adjunto->nombre_original }}">Descargar</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </main>

    <div class="modal-imagen" id="modal-imagen" onclick="cerrarImagen()">
        <span class="cerrar" onclick="cerrarImagen()">&times;</span>
        <img id="modal-imagen-img" src="" alt="Imagen ampliada">
        <div class="visor-doc" id="visor-doc" style="display:none;" onclick="event.stopPropagation()">
            <div class="visor-barra">
                <span id="visor-doc-nombre"></span>
                <a id="visor-doc-enlace" href="#" target="_blank" rel="noopener">Abrir en pestaña nueva &nearr;</a>
            </div>
            <iframe id="visor-doc-frame" src="" title="Documento adjunto"></iframe>
        </div>
    </div>

    <script>
        const modal = document.getElementById('modal-imagen');
        const modalImg = document.getElementById('modal-imagen-img');
        const visorDoc = document.getElementById('visor-doc');
        const visorFrame = document.getElementById('visor-doc-frame');

        function ampliarImagen(src) {
            modalImg.src = src;
            modalImg.style.display = '';
            visorDoc.style.display = 'none';
            modal.classList.add('activo');
        }

        /**
         * Muestra el adjunto sin salir de la pagina: las imagenes a pantalla
         * completa y los documentos (PDF, XML) dentro del visor del navegador.
         */
        function previsualizar(url, nombre, esImagen) {
            if (esImagen) {
                ampliarImagen(url);
                return;
            }

            visorFrame.src = url;
            visorFrame.title = nombre;
            document.getElementById('visor-doc-nombre').textContent = nombre;
            const enlace = document.getElementById('visor-doc-enlace');
            enlace.href = url;
            modalImg.style.display = 'none';
            visorDoc.style.display = '';
            modal.classList.add('activo');
        }

        function cerrarImagen() {
            modal.classList.remove('activo');
            // Se descarga el documento del visor para que no siga sonando o
            // consumiendo memoria mientras el modal esta cerrado.
            visorFrame.src = '';
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarImagen();
        });
    </script>
</body>
</html>
