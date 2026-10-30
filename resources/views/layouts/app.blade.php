<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $nombreSistema)</title>
    @include('partials.favicon')
    <style>
        :root {
            --azul-oscuro: #0b2545;
            --azul: #13315c;
            --azul-claro: #1d4e89;
            --blanco: #ffffff;
            --gris-claro: #f4f6f9;
            --gris-borde: #dde3ea;
            --rojo: #b3261e;
            --verde: #1e7a3e;
            --naranja: #b56a00;
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
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 64px;
        }
        header.topbar .brand {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        header.topbar .brand img {
            height: 32px;
            width: 32px;
            object-fit: contain;
            border-radius: 4px;
            background: var(--blanco);
        }
        nav.mainnav {
            background: var(--azul);
            display: flex;
            gap: 4px;
            padding: 0 16px;
            flex-wrap: wrap;
            position: relative;
        }
        nav.mainnav > a, nav.mainnav .grupo-boton {
            color: var(--blanco);
            text-decoration: none;
            padding: 12px 16px;
            font-size: 14px;
            opacity: 0.85;
            border-bottom: 3px solid transparent;
        }
        nav.mainnav > a:hover, nav.mainnav > a.active,
        nav.mainnav .grupo-boton:hover, nav.mainnav .nav-grupo.activo .grupo-boton {
            opacity: 1;
            border-bottom: 3px solid var(--blanco);
        }

        /* Menus agrupados: el sistema crecio y 14 enlaces sueltos ya no
           entraban en una linea. Se abren al hacer clic (no al pasar el
           mouse) para que tambien funcionen en pantallas tactiles. */
        .nav-grupo { position: relative; }
        .grupo-boton {
            background: none;
            border: 0;
            border-radius: 0;
            width: auto;
            margin: 0;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .grupo-boton .flecha { font-size: 10px; transition: transform .15s ease; }
        .nav-grupo.abierto .grupo-boton .flecha { transform: rotate(180deg); }
        .grupo-menu {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            min-width: 210px;
            background: var(--azul-oscuro);
            border-radius: 0 0 6px 6px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .3);
            z-index: 60;
            padding: 4px 0;
        }
        .nav-grupo.abierto .grupo-menu { display: block; }
        .grupo-menu a {
            display: block;
            color: var(--blanco);
            text-decoration: none;
            padding: 10px 16px;
            font-size: 14px;
            opacity: .85;
            white-space: nowrap;
        }
        .grupo-menu a:hover, .grupo-menu a.active {
            opacity: 1;
            background: var(--azul-claro);
        }
        main.container {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px 48px;
        }
        .card {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h1, h2, h3 { color: var(--azul-oscuro); }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th {
            background: var(--azul-oscuro);
            color: var(--blanco);
            text-align: left;
            padding: 10px 12px;
            font-size: 13px;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--gris-borde);
            font-size: 14px;
        }
        table tr:hover td { background: var(--gris-claro); }
        .btn {
            display: inline-block;
            background: var(--azul-oscuro);
            color: var(--blanco);
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover { background: var(--azul-claro); }
        .btn-secondary { background: var(--blanco); color: var(--azul-oscuro); border: 1px solid var(--azul-oscuro); }
        .btn-danger { background: var(--rojo); }
        .btn-sm { padding: 4px 10px; font-size: 12px; }
        .actions { display: flex; gap: 8px; }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--azul-oscuro); }
        input, select, textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            margin-bottom: 14px;
            font-size: 14px;
        }
        /*
         * Las casillas y radios no deben estirarse con la regla de arriba:
         * Safari en iOS los agranda y terminan empujando el texto de su
         * etiqueta encima del boton que sigue.
         */
        input[type="checkbox"], input[type="radio"] {
            width: auto;
            padding: 0;
            margin: 0;
            flex: none;
        }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 16px; }
        .grid-4 { display: grid; grid-template-columns: 2fr 1fr 1fr 2fr; gap: 0 16px; }
        .grid-5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0 16px; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            color: var(--blanco);
        }
        .badge-activo { background: var(--verde); }
        .badge-suspendido { background: var(--naranja); }
        .badge-retirado { background: var(--rojo); }
        .badge-inactivo { background: #6b7280; }
        .badge-excluido { background: #4b5563; }
        .badge-carnet-tiene { background: var(--verde); }
        .badge-carnet-extraviado { background: var(--naranja); }
        .badge-carnet-no_tiene { background: #6b7280; }
        .badge-partido-programado { background: var(--azul-claro); }
        .badge-partido-jugado { background: var(--verde); }
        .badge-partido-aplazado { background: var(--naranja); }
        .badge-partido-suspendido { background: var(--naranja); }
        .badge-partido-cancelado { background: var(--rojo); }
        .text-center { text-align: center; }
        .alert {
            background: #e7f0fb;
            border: 1px solid var(--azul-claro);
            color: var(--azul-oscuro);
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
        }
        .alert-error {
            background: #fbeaea;
            border-color: var(--rojo);
            color: var(--rojo);
        }
        .text-right { text-align: right; }
        .deuda-positiva { color: var(--rojo); font-weight: 700; }
        .deuda-cero { color: var(--verde); font-weight: 700; }

        nav[role="navigation"] { margin-top: 12px; }
        .pagination {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 4px;
            flex-wrap: wrap;
        }
        .pagination .page-item .page-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            background: var(--blanco);
            color: var(--azul-oscuro);
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 0;
        }
        .pagination .page-item.active .page-link {
            background: var(--azul-oscuro);
            color: var(--blanco);
            border-color: var(--azul-oscuro);
        }
        .pagination .page-item.disabled .page-link {
            color: #aab4c0;
            cursor: default;
        }
        .pagination .page-item:not(.disabled) .page-link:hover {
            background: var(--gris-claro);
        }

        .breadcrumbs {
            font-size: 13px;
            color: #666;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }
        .breadcrumbs a {
            color: var(--azul-claro);
            text-decoration: none;
        }
        .breadcrumbs a:hover { text-decoration: underline; }
        .breadcrumbs .breadcrumb-sep { color: #aab4c0; }
        .breadcrumbs .breadcrumb-current { color: var(--azul-oscuro); font-weight: 600; }

        .table-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }

        @media (max-width: 900px) {
            .grid-4, .grid-5 { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 768px) {
            header.topbar {
                height: auto;
                flex-wrap: wrap;
                padding: 12px 16px;
                gap: 10px;
            }
            /* Con el menu agrupado ya caben pocos elementos: se deja que
               bajen de linea en vez de desplazarse de lado. El scroll
               horizontal ademas recortaba los desplegables. */
            nav.mainnav { padding: 0 8px; }
            nav.mainnav > a, nav.mainnav .grupo-boton { padding: 12px 10px; font-size: 13px; }
            /* El desplegable se ancla al menu completo para que nunca se
               salga por el borde derecho de la pantalla. */
            .nav-grupo { position: static; }
            .grupo-menu { left: 8px; right: 8px; min-width: 0; }
            main.container { padding: 0 10px 32px; margin: 16px auto; }
            .card { padding: 14px; }
            .grid-2, .grid-4, .grid-5 { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            .actions { flex-wrap: wrap; }
            h1 { font-size: 20px; }
            h2 { font-size: 17px; }
        }

        @media (max-width: 480px) {
            header.topbar .brand { font-size: 17px; }
            .btn { padding: 8px 12px; font-size: 13px; }
            .btn-sm { padding: 5px 8px; font-size: 11px; }
        }
    </style>
    @include('partials.desliz-tabla')
</head>
<body>
    <header class="topbar">
        <div class="brand">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo">
            @endif
            {{ $nombreSistema }}
        </div>
        <div style="display:flex; align-items:center; gap:14px; font-size:13px;">
            <span>{{ auth()->user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" style="background:none; border:1px solid rgba(255,255,255,.5); color:#fff; padding:5px 12px; border-radius:6px; cursor:pointer; font-size:13px;">Cerrar sesion</button>
            </form>
            <form id="form-logout-inactividad" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        </div>
    </header>
    @php
        // El menu se arma aqui para no repetir markup: cada grupo es un
        // desplegable y los enlaces sueltos son los de uso diario.
        $grupos = [
            'Competencia' => [
                ['titulo' => 'Torneos', 'url' => route('torneos.index'), 'activo' => request()->routeIs('torneos.*')],
                ['titulo' => 'Partidos', 'url' => route('partidos.index'), 'activo' => request()->routeIs('partidos.*') && ! request()->routeIs('partidos.posiciones')],
                ['titulo' => 'Tabla de posiciones', 'url' => route('partidos.posiciones'), 'activo' => request()->routeIs('partidos.posiciones')],
                ['titulo' => 'Sanciones', 'url' => route('sanciones.index'), 'activo' => request()->routeIs('sanciones.*')],
                ['titulo' => 'Planilla de juego', 'url' => route('planilla-juego.index'), 'activo' => request()->routeIs('planilla-juego.*')],
            ],
            'Finanzas' => [
                ['titulo' => 'Tipos de cargo', 'url' => route('tipos-cargo.index'), 'activo' => request()->routeIs('tipos-cargo.*')],
                ['titulo' => 'Cuota moderada', 'url' => route('cuota-moderada.index'), 'activo' => request()->routeIs('cuota-moderada.*')],
                ['titulo' => 'Cierre de caja', 'url' => route('cierre-caja.index'), 'activo' => request()->routeIs('cierre-caja.*')],
                ['titulo' => 'Reportes contables', 'url' => route('reportes-contables.index'), 'activo' => request()->routeIs('reportes-contables.*')],
            ],
            'Sistema' => [
                ['titulo' => 'Importar datos', 'url' => route('importar.index'), 'activo' => request()->routeIs('importar.*')],
                ['titulo' => 'Configuracion', 'url' => route('configuracion.index'), 'activo' => request()->routeIs('configuracion.*')],
                ['titulo' => 'Cambiar contrasena', 'url' => route('password.edit'), 'activo' => request()->routeIs('password.*')],
            ],
        ];
    @endphp
    <nav class="mainnav">
        <a href="{{ route('socios.index') }}" class="{{ request()->routeIs('socios.*') ? 'active' : '' }}">Socios</a>
        <a href="{{ route('equipos.index') }}" class="{{ request()->routeIs('equipos.*') ? 'active' : '' }}">Equipos</a>

        @foreach ($grupos as $nombreGrupo => $enlaces)
            @php($grupoActivo = collect($enlaces)->contains('activo', true))
            <div class="nav-grupo {{ $grupoActivo ? 'activo' : '' }}">
                <button type="button" class="grupo-boton" aria-expanded="false">
                    {{ $nombreGrupo }} <span class="flecha">&#9660;</span>
                </button>
                <div class="grupo-menu">
                    @foreach ($enlaces as $enlace)
                        <a href="{{ $enlace['url'] }}" class="{{ $enlace['activo'] ? 'active' : '' }}">{{ $enlace['titulo'] }}</a>
                    @endforeach
                </div>
            </div>
        @endforeach

        <a href="{{ route('noticias-admin.index') }}" class="{{ request()->routeIs('noticias-admin.*') ? 'active' : '' }}">Noticias</a>
    </nav>
    <script>
        /**
         * Los grupos del menu se abren con clic (no con el mouse encima)
         * para que funcionen igual en telefono. Solo uno abierto a la vez.
         */
        (function () {
            const grupos = Array.prototype.slice.call(document.querySelectorAll('.nav-grupo'));

            function cerrarTodos(excepto) {
                grupos.forEach(function (grupo) {
                    if (grupo === excepto) return;
                    grupo.classList.remove('abierto');
                    grupo.querySelector('.grupo-boton').setAttribute('aria-expanded', 'false');
                });
            }

            grupos.forEach(function (grupo) {
                const boton = grupo.querySelector('.grupo-boton');
                boton.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const seAbre = !grupo.classList.contains('abierto');
                    cerrarTodos(grupo);
                    grupo.classList.toggle('abierto', seAbre);
                    boton.setAttribute('aria-expanded', seAbre ? 'true' : 'false');
                });
            });

            document.addEventListener('click', function () { cerrarTodos(null); });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') cerrarTodos(null);
            });
        })();
    </script>
    <main class="container">
        @if (session('status'))
            <div class="alert">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <ul style="margin:0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
    @include('partials.footer-firma')

    <script>
        (function () {
            const MINUTOS_INACTIVIDAD = 20;
            const LIMITE_MS = MINUTOS_INACTIVIDAD * 60 * 1000;
            let temporizador;

            function cerrarSesionPorInactividad() {
                document.getElementById('form-logout-inactividad').submit();
            }

            function reiniciarTemporizador() {
                clearTimeout(temporizador);
                temporizador = setTimeout(cerrarSesionPorInactividad, LIMITE_MS);
            }

            ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evento) {
                document.addEventListener(evento, reiniciarTemporizador, { passive: true });
            });

            reiniciarTemporizador();
        })();
    </script>
</body>
</html>
