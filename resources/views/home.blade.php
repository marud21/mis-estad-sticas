<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $nombreSistema }}</title>
    {{-- Permite agregar el sitio a la pantalla de inicio, unico modo de
         recibir avisos en iPhone. --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0b2545">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Corvepatios">
    @include('partials.favicon')
    @include('partials.analitica')
    <style>
        :root {
            --azul-oscuro: #0b2545;
            --azul-claro: #1d4e89;
            --blanco: #ffffff;
            --gris-claro: #f4f6f9;
            --gris-borde: #dde3ea;
            --rojo: #b3261e;
            --verde: #1e7a3e;
            --amarillo: #d4a017;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gris-claro);
            color: #1a1a1a;
        }

        header.portada {
            background: var(--azul-oscuro);
            color: var(--blanco);
            padding: 22px 24px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        header.portada img {
            height: 52px; width: 52px; object-fit: contain;
            border-radius: 8px; background: var(--blanco);
        }
        header.portada h1 { margin: 0; font-size: 24px; }
        header.portada p { margin: 4px 0 0; opacity: .85; font-size: 14px; max-width: 640px; }

        main.container { max-width: 960px; margin: 24px auto; padding: 0 16px 48px; }
        .card {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 18px;
        }
        h2 {
            color: var(--azul-oscuro); font-size: 17px;
            margin: 0 0 12px; display: flex; align-items: center; gap: 8px;
        }
        h3 { color: var(--azul-claro); font-size: 14px; margin: 18px 0 8px; text-transform: uppercase; letter-spacing: .4px; }
        h3:first-of-type { margin-top: 0; }
        .sub { color: #777; font-size: 13px; margin: 0 0 16px; }
        .vacio { color: #999; font-size: 14px; padding: 6px 0; }

        .selector { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 4px; }
        .selector select {
            padding: 8px 10px; border: 1px solid var(--gris-borde);
            border-radius: 6px; font-size: 14px; background: var(--blanco);
            /* Un torneo con nombre muy largo estiraba el selector de lado a
               lado y se montaba sobre el dibujo del fondo. La lista
               desplegada si muestra el nombre completo. */
            max-width: 100%;
        }
        .franja-superior.con-cumple .selector select { max-width: 260px; }

        .noticias { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
        .noticias > * { min-width: 0; }
        .noticia {
            display: block; text-decoration: none; color: inherit;
            border: 1px solid var(--gris-borde); border-radius: 8px;
            padding: 14px; background: var(--gris-claro);
        }
        .noticia:hover { border-color: var(--azul-claro); }
        .noticia h4 { margin: 0 0 4px; font-size: 15px; color: var(--azul-oscuro); }
        .noticia .fecha { font-size: 12px; color: #888; margin: 0 0 6px; }
        .noticia p { margin: 0; font-size: 13px; color: #555; line-height: 1.5; }

        footer {
            text-align: center; padding: 20px 16px 28px;
            color: #8a94a0; font-size: 13px;
        }

        /* Primera franja: el encabezado del torneo y, a su derecha, el
           saludo de cumpleaños. Sin cumpleañeros no hay segunda columna y
           el encabezado ocupa todo el ancho. */
        .franja-superior.con-cumple {
            display: grid;
            grid-template-columns: 1fr minmax(240px, 320px);
            gap: 18px;
            /* Las dos tarjetas quedan de la misma altura, la del contenido
               mas alto. Con "start" cada una tomaba la suya y la mas corta
               dejaba un hueco al lado. */
            align-items: stretch;
        }
        .franja-superior.con-cumple > * { min-width: 0; }

        /* Dibujo de una cancha al fondo, a la derecha, en azul muy tenue.
           Va como SVG dentro del mismo CSS y no como imagen aparte: no hay
           archivo que cargar ni que se pueda perder, y se ve nitido en
           cualquier pantalla. */
        .cabecera-torneo {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 130'%3E%3Cg fill='none' stroke='%231d4e89' stroke-opacity='0.16' stroke-width='3'%3E%3Crect x='5' y='5' width='190' height='120' rx='3'/%3E%3Cline x1='100' y1='5' x2='100' y2='125'/%3E%3Ccircle cx='100' cy='65' r='26'/%3E%3Crect x='5' y='35' width='28' height='60'/%3E%3Crect x='167' y='35' width='28' height='60'/%3E%3C/g%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: auto 78%;
            transition: background-position .35s ease, box-shadow .25s ease, border-color .25s ease;
        }

        /* Al pasar el mouse la cancha entra un poco y la tarjeta se levanta.
           En telefono no hay mouse, asi que alli queda el fondo quieto: el
           efecto es un adorno, nada depende de el. */
        .cabecera-torneo:hover {
            background-position: right 24px center;
            box-shadow: 0 6px 18px rgba(11, 37, 69, .12);
            border-color: var(--azul-claro);
        }

        /* Por debajo de ~1000px la tarjeta ya no es lo bastante ancha para
           que la cancha quepa al lado del texto, asi que se achica a la
           esquina de arriba. Es el ancho donde el selector de torneo
           empezaba a quedar encima del dibujo. */
        @media (max-width: 1000px) {
            .cabecera-torneo {
                background-position: right 12px top 12px;
                background-size: auto 34%;
            }
            .cabecera-torneo:hover { background-position: right 12px top 12px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .cabecera-torneo { transition: none; }
        }

        /* Al estirarse, el sobrante se reparte arriba y abajo en vez de
           quedar todo al pie de la tarjeta. */
        .franja-superior.con-cumple > .cabecera-torneo {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .franja-superior.con-cumple > .cabecera-torneo > :first-child { margin-top: 0; }
        .franja-superior.con-cumple > .cabecera-torneo > :last-child { margin-bottom: 0; }

        /* Columna, para que si la tarjeta de al lado es la mas alta el
           sobrante se vaya al mensaje del final y no quede un hueco. */
        .cumple {
            border-top: 3px solid var(--amarillo);
            display: flex;
            flex-direction: column;
        }
        .cumple-titulo { font-size: 16px; margin: 0 0 10px; }
        .cumple-lista { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
        .cumple-socio { display: flex; align-items: center; gap: 10px; }
        .cumple-foto {
            width: 52px; height: 52px; flex: none;
            object-fit: cover; border-radius: 50%;
            border: 2px solid var(--amarillo);
        }
        .cumple-iniciales {
            display: flex; align-items: center; justify-content: center;
            background: var(--gris-claro); color: var(--azul-oscuro);
            font-weight: 700; font-size: 17px;
        }
        .cumple-datos { display: flex; flex-direction: column; min-width: 0; }
        .cumple-datos strong { color: var(--azul-oscuro); font-size: 14px; line-height: 1.3; }
        .cumple-datos span { color: #666; font-size: 12px; }
        .cumple-mensaje { margin: 12px 0 0; font-size: 12px; color: #666; }
        .franja-superior.con-cumple .cumple-mensaje { margin-top: auto; padding-top: 12px; }

        /* Carrusel: solo se activa cuando el guion marca la lista. Sin el,
           la lista sigue siendo una lista normal con todos a la vista. */
        .cumple-lista.carrusel > .cumple-socio { display: none; }
        .cumple-lista.carrusel > .cumple-socio.activo {
            display: flex;
            animation: aparecer .35s ease;
        }
        @keyframes aparecer {
            from { opacity: 0; transform: translateX(8px); }
            to { opacity: 1; transform: none; }
        }

        .cumple-puntos { display: none; gap: 6px; margin-top: 12px; }
        .cumple-lista.carrusel ~ .cumple-puntos { display: flex; }
        .cumple-punto {
            width: 8px; height: 8px; padding: 0;
            border: 0; border-radius: 50%;
            background: var(--gris-borde);
            cursor: pointer;
            transition: background .2s;
        }
        .cumple-punto.activo { background: var(--amarillo); }

        /* A quien pidio menos movimiento se le cambia igual, pero sin la
           animacion de entrada. */
        @media (prefers-reduced-motion: reduce) {
            .cumple-lista.carrusel > .cumple-socio.activo { animation: none; }
        }

        @media (max-width: 760px) {
            main.container { padding: 0 10px 32px; margin: 16px auto; }
            .card { padding: 14px; }
            header.portada { padding: 16px; }
            header.portada h1 { font-size: 20px; }
            .noticias { grid-template-columns: 1fr; }
            /* En telefono el saludo pasa a ocupar todo el ancho, debajo. */
            .franja-superior.con-cumple { grid-template-columns: 1fr; }
        }
    </style>
    @include('partials.estadisticas-estilos')
    @include('partials.desliz-tabla')
</head>
<body>
    <header class="portada">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo">
        @endif
        <div>
            <h1>{{ $nombreSistema }}</h1>
            <p>{{ $descripcionPortada }}</p>
        </div>
    </header>

    @include('partials.nav-publico')

    <main class="container">
        @include('partials.avisos-push')

        {{-- El encabezado y el saludo de cumpleaños comparten la primera
             franja: el saludo a la derecha, y debajo en telefono. --}}
        <div class="franja-superior {{ $cumpleaneros->isNotEmpty() ? 'con-cumple' : '' }}">
        @if (! $torneo)
            <div class="card cabecera-torneo">
                <h2>&#127942; Estadisticas del torneo</h2>
                <p class="vacio">Todavia no hay torneos publicados.</p>
            </div>
        @else
            <div class="card cabecera-torneo">
                <h2 style="font-size:20px;">{{ $torneo->nombre }}</h2>
                <p class="sub" style="margin-bottom:0;">
                    @if ($torneo->fecha_inicio)
                        Desde el {{ $torneo->fecha_inicio->format('d/m/Y') }}
                        @if ($torneo->fecha_fin) hasta el {{ $torneo->fecha_fin->format('d/m/Y') }} @endif
                    @endif
                </p>

                @if ($torneos->count() > 1)
                    <form class="selector" method="GET" action="{{ route('home') }}" style="margin-top:14px;">
                        <label for="torneo_id">Torneo:</label>
                        <select name="torneo_id" id="torneo_id" onchange="this.form.submit()">
                            @foreach ($torneos as $t)
                                <option value="{{ $t->id }}" @selected($t->id === $torneo->id)>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                        <noscript><button type="submit">Ver</button></noscript>
                    </form>
                @endif
            </div>
        @endif
            @include('partials.cumpleanos')
        </div>

        @if ($torneo)
            @include('partials.estadisticas-torneo')
        @endif

        @php
            $ultimasNoticias = \App\Models\Noticia::where('publicado', true)
                ->orderByDesc('fecha_publicacion')->orderByDesc('id')->limit(3)->get();
        @endphp
        @if ($ultimasNoticias->isNotEmpty())
            <div class="card">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                    <h2 style="margin:0;">&#128240; Ultimas noticias</h2>
                    <a href="{{ route('noticias-publicas.index') }}" style="color:var(--azul-claro); font-size:14px; font-weight:600; text-decoration:none;">Ver todas &rarr;</a>
                </div>
                <div class="noticias" style="margin-top:14px;">
                    @foreach ($ultimasNoticias as $noticia)
                        <a href="{{ route('noticias-publicas.show', $noticia) }}" class="noticia">
                            <h4>{{ $noticia->titulo }}</h4>
                            <p class="fecha">{{ $noticia->fecha_publicacion->format('d/m/Y') }}</p>
                            <p>{{ Illuminate\Support\Str::limit(strip_tags($noticia->contenido), 100) }}</p>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    <footer>
        <p style="margin:0 0 4px;">&copy; {{ date('Y') }} {{ $nombreSistema }}</p>
        @include('partials.footer-firma')
    </footer>
    @include('partials.aviso-cookies')
</body>
</html>
