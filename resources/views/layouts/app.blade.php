<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
        nav.mainnav a {
            color: var(--blanco);
            text-decoration: none;
            padding: 12px 16px;
            font-size: 14px;
            opacity: 0.85;
            border-bottom: 3px solid transparent;
        }
        nav.mainnav a:hover, nav.mainnav a.active {
            opacity: 1;
            border-bottom: 3px solid var(--blanco);
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
            nav.mainnav {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                white-space: nowrap;
                flex-wrap: nowrap;
                padding: 0 8px;
            }
            nav.mainnav a { padding: 12px 10px; font-size: 13px; }
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
        </div>
    </header>
    <nav class="mainnav">
        <a href="{{ route('socios.index') }}" class="{{ request()->routeIs('socios.*') ? 'active' : '' }}">Socios</a>
        <a href="{{ route('equipos.index') }}" class="{{ request()->routeIs('equipos.*') ? 'active' : '' }}">Equipos</a>
        <a href="{{ route('torneos.index') }}" class="{{ request()->routeIs('torneos.*') ? 'active' : '' }}">Torneos</a>
        <a href="{{ route('planilla-juego.index') }}" class="{{ request()->routeIs('planilla-juego.*') ? 'active' : '' }}">Planilla de juego</a>
        <a href="{{ route('tipos-cargo.index') }}" class="{{ request()->routeIs('tipos-cargo.*') ? 'active' : '' }}">Tipos de cargo</a>
        <a href="{{ route('reportes-contables.index') }}" class="{{ request()->routeIs('reportes-contables.*') ? 'active' : '' }}">Reportes</a>
        <a href="{{ route('cierre-caja.index') }}" class="{{ request()->routeIs('cierre-caja.*') ? 'active' : '' }}">Cierre de caja</a>
        <a href="{{ route('importar.index') }}" class="{{ request()->routeIs('importar.*') ? 'active' : '' }}">Importar</a>
        <a href="{{ route('configuracion.index') }}" class="{{ request()->routeIs('configuracion.*') ? 'active' : '' }}">Configuracion</a>
        <a href="{{ route('password.edit') }}" class="{{ request()->routeIs('password.*') ? 'active' : '' }}">Cambiar contrasena</a>
    </nav>
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
</body>
</html>
