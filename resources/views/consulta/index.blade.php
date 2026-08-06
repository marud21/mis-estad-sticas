<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de socio - {{ $nombreSistema }}</title>
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
            gap: 10px;
        }
        header.topbar img {
            height: 28px;
            width: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: var(--blanco);
        }
        main.container {
            max-width: 700px;
            margin: 32px auto;
            padding: 0 16px 48px;
        }
        .card {
            background: var(--blanco);
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 20px;
        }
        h1 { color: var(--azul-oscuro); font-size: 20px; margin-top: 0; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: var(--azul-oscuro); }
        input[type=text] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            margin-bottom: 14px;
            font-size: 14px;
        }
        .btn {
            background: var(--azul-oscuro);
            color: var(--blanco);
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
        }
        .btn:hover { background: var(--azul-claro); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table th {
            background: var(--azul-oscuro);
            color: var(--blanco);
            text-align: left;
            padding: 8px 10px;
            font-size: 13px;
        }
        table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--gris-borde);
            font-size: 14px;
        }
        .deuda-positiva { color: var(--rojo); font-weight: 700; }
        .deuda-cero { color: var(--verde); font-weight: 700; }
        .alert-error {
            background: #fbeaea;
            border: 1px solid var(--rojo);
            color: var(--rojo);
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .cuenta-bancaria {
            border: 1px solid var(--gris-borde);
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        .cuenta-bancaria strong { color: var(--azul-oscuro); }
        .alert-info {
            background: #fff8e1;
            border: 1px solid #d4a017;
            color: #6b5200;
            padding: 10px 14px;
            border-radius: 6px;
            margin-top: 14px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <header class="topbar">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="Logo">
        @endif
        {{ $nombreSistema }} &mdash; Consulta de socio
    </header>
    <main class="container">
        <div class="card">
            <h1>Consultar mis cargos y pagos</h1>
            <form action="{{ route('consulta.buscar') }}" method="POST">
                @csrf
                <label>Numero de documento</label>
                <input type="text" name="numero_documento" value="{{ old('numero_documento') }}" required autofocus>
                <button class="btn" type="submit">Consultar</button>
            </form>
        </div>

        @if ($buscado)
            <div class="card">
                @if (! $socio)
                    <div class="alert-error">No se encontro ningun socio con ese numero de documento.</div>
                @else
                    <h1>{{ $socio->nombre_completo }}</h1>

                    <h2 style="font-size:16px; color: var(--azul-oscuro);">Cargos</h2>
                    <table>
                        <thead><tr><th>Tipo</th><th>Monto</th><th>Fecha</th></tr></thead>
                        <tbody>
                            @forelse ($socio->cargos as $cargo)
                                <tr>
                                    <td>{{ $cargo->tipoCargo->nombre }}</td>
                                    <td>${{ number_format($cargo->monto, 0, ',', '.') }}</td>
                                    <td>{{ $cargo->fecha->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Sin cargos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <h2 style="font-size:16px; color: var(--azul-oscuro); margin-top:20px;">Pagos</h2>
                    <table>
                        <thead><tr><th>Valor</th><th>Fecha</th><th>Tipo</th></tr></thead>
                        <tbody>
                            @forelse ($socio->pagos as $pago)
                                <tr>
                                    <td>${{ number_format($pago->valor, 0, ',', '.') }}</td>
                                    <td>{{ $pago->fecha->format('d/m/Y') }}</td>
                                    <td>{{ ucfirst($pago->tipo) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3">Sin pagos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>

                    <p style="margin-top:20px;">
                        <strong>Deuda total:
                            <span class="{{ $socio->deuda_total > 0 ? 'deuda-positiva' : 'deuda-cero' }}">
                                ${{ number_format($socio->deuda_total, 0, ',', '.') }}
                            </span>
                        </strong>
                    </p>
                @endif
            </div>
        @endif

        @if ($cuentasBancarias->isNotEmpty() || $whatsappCorporacion)
            <div class="card">
                <h1>Formas de pago</h1>
                @if ($cuentasBancarias->isNotEmpty())
                    <p style="font-size:13px; color:#555;">Puedes hacer tu pago o transferencia a cualquiera de estas cuentas:</p>
                    @foreach ($cuentasBancarias as $cuenta)
                        <div class="cuenta-bancaria">
                            <strong>{{ $cuenta->banco }}</strong> &mdash; {{ $cuenta->tipo_cuenta }}<br>
                            Numero: {{ $cuenta->numero_cuenta }}<br>
                            Titular: {{ $cuenta->titular }}
                        </div>
                    @endforeach
                @endif

                @if ($whatsappCorporacion)
                    <div class="alert-info">
                        Todo pago realizado de manera virtual debe enviarse (foto del comprobante o recibo) al WhatsApp de la corporacion:
                        <strong>{{ $whatsappCorporacion }}</strong>
                    </div>
                @endif
            </div>
        @endif
    </main>
    @include('partials.footer-firma')
</body>
</html>
