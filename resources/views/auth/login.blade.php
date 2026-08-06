<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesion - {{ $nombreSistema }}</title>
    @include('partials.favicon')
    <style>
        :root {
            --azul-oscuro: #0b2545;
            --azul-claro: #1d4e89;
            --blanco: #ffffff;
            --gris-claro: #f4f6f9;
            --gris-borde: #dde3ea;
            --rojo: #b3261e;
        }
        html, body { height: 100%; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 24px 16px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--azul-oscuro), var(--azul-claro));
        }
        .login-card {
            background: var(--blanco);
            padding: 32px;
            border-radius: 10px;
            width: 100%;
            max-width: 360px;
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
        }
        input[type=email], input[type=password] {
            -webkit-appearance: none;
            appearance: none;
        }

        @media (max-width: 420px) {
            body { padding: 16px 10px; }
            .login-card { padding: 22px 18px; max-width: 100%; }
            h1 { font-size: 19px; }
        }
        h1 { color: var(--azul-oscuro); font-size: 22px; margin-top: 0; text-align: center; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--azul-oscuro); }
        input[type=email], input[type=password] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 16px;
        }
        .btn {
            width: 100%;
            background: var(--azul-oscuro);
            color: var(--blanco);
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        .btn:hover { background: var(--azul-claro); }
        .alert-error {
            background: #fbeaea;
            border: 1px solid var(--rojo);
            color: var(--rojo);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .public-link {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
        }
        .public-link a { color: var(--azul-claro); }
    </style>
</head>
<body>
    <div class="login-card">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo" style="display:block; height:56px; width:56px; object-fit:contain; margin:0 auto 12px;">
        @endif
        <h1>{{ $nombreSistema }} &mdash; Administracion</h1>
        @if ($errors->any())
            <div class="alert-error">
                {{ $errors->first() }}
            </div>
        @endif
        <form action="{{ route('login.store') }}" method="POST">
            @csrf
            <label>Correo electronico</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            <label>Contrasena</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">Ingresar</button>
        </form>
        <div class="public-link">
            <a href="{{ route('consulta.index') }}">Consultar deuda de un socio &rarr;</a>
        </div>
    </div>
    @include('partials.footer-firma')
</body>
</html>
