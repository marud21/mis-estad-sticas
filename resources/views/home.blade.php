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
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gris-claro);
            color: #1a1a1a;
        }
        header.hero {
            background: linear-gradient(135deg, var(--azul-oscuro), var(--azul-claro));
            color: var(--blanco);
            padding: 56px 24px;
            text-align: center;
        }
        header.hero h1 {
            margin: 0 0 12px;
            font-size: 32px;
            letter-spacing: 0.5px;
        }
        header.hero p {
            margin: 0 auto;
            max-width: 560px;
            font-size: 16px;
            opacity: 0.92;
            line-height: 1.5;
        }
        .acciones {
            display: flex;
            justify-content: center;
            gap: 14px;
            margin-top: 28px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 12px 26px;
            border-radius: 6px;
            font-size: 15px;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-primary {
            background: var(--blanco);
            color: var(--azul-oscuro);
        }
        .btn-primary:hover { background: #e9eef5; }
        .btn-secondary {
            background: transparent;
            color: var(--blanco);
            border: 1px solid rgba(255,255,255,.6);
        }
        .btn-secondary:hover { background: rgba(255,255,255,.1); }
        main.container {
            max-width: 900px;
            margin: 0 auto;
            padding: 40px 20px 60px;
        }
        .caracteristicas {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .caracteristica {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 20px;
        }
        .caracteristica h3 {
            color: var(--azul-oscuro);
            font-size: 16px;
            margin-top: 0;
        }
        .caracteristica p {
            font-size: 14px;
            color: #555;
            line-height: 1.5;
            margin-bottom: 0;
        }
        footer {
            text-align: center;
            font-size: 12px;
            color: #888;
            padding: 20px;
        }

        @media (max-width: 700px) {
            .caracteristicas { grid-template-columns: 1fr; }
            header.hero h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <header class="hero">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" style="height:64px; width:64px; object-fit:contain; border-radius:8px; background:#fff; margin-bottom:12px;">
        @endif
        <h1>{{ $nombreSistema }}</h1>
        <p>{{ $descripcionPortada }}</p>
        <div class="acciones">
            <a href="{{ route('consulta.index') }}" class="btn btn-primary">Consultar mi deuda</a>
            @auth
                <a href="{{ route('socios.index') }}" class="btn btn-secondary">Ir al panel</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-secondary">Iniciar sesion</a>
            @endauth
        </div>
    </header>

    <main class="container">
        <div class="caracteristicas">
            <div class="caracteristica">
                <h3>Socios y equipos</h3>
                <p>Registro completo de socios, sus equipos, posicion y estado (activo, suspendido o retirado).</p>
            </div>
            <div class="caracteristica">
                <h3>Cargos y pagos</h3>
                <p>Control de afiliaciones, mensualidades y otros cargos, con el historial de pagos de cada socio.</p>
            </div>
            <div class="caracteristica">
                <h3>Consulta de deuda</h3>
                <p>Cualquier socio puede consultar su deuda actual y las formas de pago disponibles, sin necesidad de una cuenta.</p>
            </div>
        </div>
    </main>

    <footer>
        <p style="margin:0 0 4px;">&copy; {{ date('Y') }} {{ $nombreSistema }}</p>
        @include('partials.footer-firma')
    </footer>
</body>
</html>
