{{--
    Menu de la parte publica. La portada muestra las estadisticas, y desde
    aqui se llega al resto de secciones. Se marca la seccion actual con la
    ruta, para que el visitante sepa siempre donde esta.
--}}
<nav class="nav-publico">
    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'activo' : '' }}">Estadisticas</a>
    <a href="{{ route('noticias-publicas.index') }}" class="{{ request()->routeIs('noticias-publicas.*') ? 'activo' : '' }}">Noticias</a>
    <a href="{{ route('consulta.index') }}" class="{{ request()->routeIs('consulta.*') ? 'activo' : '' }}">Consultar mi deuda</a>
    @auth
        <a href="{{ route('socios.index') }}" class="destacado">Ir al panel</a>
    @else
        <a href="{{ route('login') }}" class="destacado">Iniciar sesion</a>
    @endauth
</nav>

<style>
    .nav-publico {
        background: var(--azul-claro);
        display: flex;
        gap: 4px;
        padding: 0 16px;
        flex-wrap: wrap;
    }
    .nav-publico a {
        color: var(--blanco);
        text-decoration: none;
        padding: 12px 16px;
        font-size: 14px;
        opacity: .85;
        border-bottom: 3px solid transparent;
        white-space: nowrap;
    }
    .nav-publico a:hover, .nav-publico a.activo {
        opacity: 1;
        border-bottom-color: var(--blanco);
    }
    .nav-publico a.destacado { margin-left: auto; font-weight: 600; }

    @media (max-width: 640px) {
        .nav-publico { padding: 0 8px; }
        .nav-publico a { padding: 11px 10px; font-size: 13px; }
        .nav-publico a.destacado { margin-left: 0; }
    }
</style>
