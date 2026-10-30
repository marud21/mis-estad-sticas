<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $nombreSistema }}</title>
    @include('partials.favicon')
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
        }

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

        @media (max-width: 760px) {
            main.container { padding: 0 10px 32px; margin: 16px auto; }
            .card { padding: 14px; }
            header.portada { padding: 16px; }
            header.portada h1 { font-size: 20px; }
            .noticias { grid-template-columns: 1fr; }
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
        @if (! $torneo)
            <div class="card">
                <h2>&#127942; Estadisticas del torneo</h2>
                <p class="vacio">Todavia no hay torneos publicados.</p>
            </div>
        @else
            <div class="card">
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
</body>
</html>
