<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias - {{ $nombreSistema }}</title>
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
            max-width: 900px;
            margin: 32px auto;
            padding: 0 16px 48px;
        }
        h1 { color: var(--azul-oscuro); font-size: 24px; margin-top: 0; }
        .noticias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .noticia-card {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .noticia-card img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            cursor: zoom-in;
        }
        .noticia-card .contenido {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .noticia-card h2 {
            font-size: 16px;
            color: var(--azul-oscuro);
            margin: 0 0 6px;
        }
        .noticia-card .fecha {
            font-size: 12px;
            color: #888;
            margin-bottom: 8px;
        }
        .noticia-card p {
            font-size: 14px;
            color: #444;
            flex: 1;
            margin: 0 0 12px;
        }
        .noticia-card a.leer-mas {
            color: var(--azul-claro);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
        }
        .noticia-card a.leer-mas:hover { text-decoration: underline; }
        .sin-noticias { color: #666; }

        nav[role="navigation"] { margin-top: 24px; }
        .pagination { display: flex; list-style: none; margin: 0; padding: 0; gap: 4px; flex-wrap: wrap; }
        .pagination .page-item .page-link {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 32px; height: 32px; padding: 0 8px;
            border: 1px solid var(--gris-borde); border-radius: 6px;
            background: var(--blanco); color: var(--azul-oscuro);
            text-decoration: none; font-size: 13px;
        }
        .pagination .page-item.active .page-link { background: var(--azul-oscuro); color: var(--blanco); border-color: var(--azul-oscuro); }
        .pagination .page-item.disabled .page-link { color: #aab4c0; }

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
        <a class="volver" href="{{ route('home') }}">&larr; Volver al inicio</a>
    </header>
    <main class="container">
        <h1>Noticias e informes</h1>

        @if ($noticias->isEmpty())
            <p class="sin-noticias">Aun no hay noticias publicadas.</p>
        @else
            <div class="noticias-grid">
                @foreach ($noticias as $noticia)
                    <div class="noticia-card">
                        @if ($noticia->imagen_path)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($noticia->imagen_path) }}" alt="{{ $noticia->titulo }}"
                                 onclick="ampliarImagen('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($noticia->imagen_path) }}')">
                        @endif
                        <div class="contenido">
                            <h2>{{ $noticia->titulo }}</h2>
                            <div class="fecha">{{ $noticia->fecha_publicacion->format('d/m/Y') }}</div>
                            <p>{{ Illuminate\Support\Str::limit(strip_tags($noticia->contenido), 140) }}</p>
                            <a class="leer-mas" href="{{ route('noticias-publicas.show', $noticia) }}">Leer mas &rarr;</a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $noticias->links() }}
        @endif
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
