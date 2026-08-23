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
        </div>
    </main>

    <div class="modal-imagen" id="modal-imagen" onclick="cerrarImagen()">
        <span class="cerrar" onclick="cerrarImagen()">&times;</span>
        <img id="modal-imagen-img" src="" alt="Imagen ampliada">
    </div>

    <script>
        function ampliarImagen(src) {
            document.getElementById('modal-imagen-img').src = src;
            document.getElementById('modal-imagen').classList.add('activo');
        }
        function cerrarImagen() {
            document.getElementById('modal-imagen').classList.remove('activo');
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cerrarImagen();
        });
    </script>
</body>
</html>
