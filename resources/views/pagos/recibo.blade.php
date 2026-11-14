<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $pago->id }}</title>
    @include('partials.favicon')
    <style>
        /*
         * Recibo para impresora POS de rollo de 80 mm.
         *
         * La impresora termica no imprime tinta: quema puntos en el papel.
         * Los trazos finos salen palidos y entrecortados, y empeoran a
         * medida que se gasta el cabezal. Por eso aqui no se usa una letra
         * fina como Courier sino una palo seco en negrita, que quema
         * trazos gruesos y se lee de corrido. Las medidas van en puntos y
         * no en pixeles porque esto se imprime, no se mira en pantalla.
         */
        @page {
            /*
             * El margen lo pone el cuerpo. Antes la pagina ponia 4 mm por
             * lado que se sumaban a los 72 mm del cuerpo: daban 80 mm
             * justos, y como el area que de verdad imprime un rollo de
             * 80 mm son unos 72, el navegador encogia todo para que
             * cupiera. De ahi venia buena parte de la letra chiquita.
             *
             * No se declara "size" a proposito: el tamaño de papel lo
             * sigue decidiendo el controlador de la impresora, igual que
             * hasta ahora.
             */
            margin: 0;
        }

        /*
         * Que el relleno quede DENTRO de los 72 mm y no se les sume. Sin
         * esto el recibo media 76 mm, no cabia en el area imprimible del
         * rollo y el navegador lo encogia entero para que cupiera: justo
         * lo que se quiere evitar.
         */
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body {
            width: 72mm;
            margin: 0 auto;
            padding: 3mm 2mm;
            font-family: Arial, Helvetica, 'Liberation Sans', sans-serif;
            font-size: 11pt;
            font-weight: 700;
            line-height: 1.45;
            color: #000;
        }

        /* Los nombres largos parten renglon en vez de desbordar el papel. */
        body, td, th, p, h1 {
            word-break: break-word;
            overflow-wrap: break-word;
        }

        h1 {
            font-size: 15pt;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.4pt;
            margin: 0 0 2pt;
        }

        .subtitulo {
            text-align: center;
            font-size: 10pt;
            letter-spacing: 1.2pt;
            margin: 0 0 6pt;
        }

        .raya {
            border: none;
            border-top: 2px solid #000;
            margin: 7pt 0;
        }

        .raya-fina {
            border: none;
            border-top: 1px solid #000;
            margin: 5pt 0;
        }

        /*
         * Cada dato va en dos renglones: arriba el rotulo pequeño, abajo
         * el valor grande. Antes iban rotulo y valor en la misma linea,
         * uno a cada lado; en 72 mm un nombre completo apretaba la
         * columna y quedaba en letra diminuta.
         */
        .dato {
            margin: 0 0 5pt;
        }

        .rotulo {
            display: block;
            font-size: 8.5pt;
            font-weight: 400;
            letter-spacing: 0.8pt;
            text-transform: uppercase;
        }

        .valor {
            display: block;
            font-size: 12pt;
            font-weight: 700;
        }

        .pareja {
            display: flex;
            justify-content: space-between;
            gap: 4mm;
        }

        .pareja .dato {
            flex: 1;
        }

        .pareja .dato + .dato {
            text-align: right;
        }

        /* Lo que la gente busca primero en el papel. */
        .total {
            text-align: center;
            margin: 6pt 0;
        }

        .total .rotulo {
            font-size: 10pt;
            font-weight: 700;
            letter-spacing: 1.5pt;
        }

        .total .valor {
            font-size: 20pt;
            line-height: 1.2;
        }

        .deuda {
            text-align: center;
            margin: 6pt 0;
        }

        .deuda .valor {
            font-size: 14pt;
        }

        .gracias {
            text-align: center;
            font-size: 11pt;
            margin: 8pt 0 0;
        }

        .btn-imprimir {
            display: block;
            margin: 14px auto;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        @media print {
            .no-print { display: none; }

            /* Que el negro salga negro y no un gris ahorrativo. */
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <h1>{{ strtoupper($nombreSistema) }}</h1>
    <p class="subtitulo">RECIBO DE PAGO</p>

    <hr class="raya">

    <div class="pareja">
        <p class="dato">
            <span class="rotulo">Recibo N&deg;</span>
            <span class="valor">{{ str_pad($pago->id, 6, '0', STR_PAD_LEFT) }}</span>
        </p>
        <p class="dato">
            <span class="rotulo">Fecha</span>
            <span class="valor">{{ $pago->fecha->format('d/m/Y') }}</span>
        </p>
    </div>

    <hr class="raya-fina">

    <p class="dato">
        <span class="rotulo">Socio</span>
        <span class="valor">{{ $pago->socio->nombre_completo }}</span>
    </p>
    <p class="dato">
        <span class="rotulo">Documento</span>
        <span class="valor">{{ $pago->socio->numero_documento }}</span>
    </p>
    @if ($pago->equipo)
        <p class="dato">
            <span class="rotulo">Equipo</span>
            <span class="valor">{{ $pago->equipo->nombre }}</span>
        </p>
    @endif
    @if ($pago->cargo)
        <p class="dato">
            <span class="rotulo">Abona a</span>
            <span class="valor">{{ $pago->cargo->tipoCargo->nombre }}</span>
        </p>
    @endif
    <p class="dato">
        <span class="rotulo">Tipo de pago</span>
        <span class="valor">{{ ucfirst($pago->tipo) }}</span>
    </p>

    <hr class="raya">

    <div class="total">
        <span class="rotulo">TOTAL PAGADO</span>
        <span class="valor">${{ number_format($pago->valor, 0, ',', '.') }}</span>
    </div>

    <hr class="raya">

    <div class="deuda">
        <span class="rotulo">Deuda total actual</span>
        <span class="valor">${{ number_format($pago->socio->deuda_total, 0, ',', '.') }}</span>
    </div>

    <hr class="raya-fina">

    <p class="gracias">Gracias por su pago</p>

    <button class="btn-imprimir no-print" onclick="window.print()">Imprimir</button>

    <div class="no-print">
        @include('partials.footer-firma')
    </div>

    <script>
        window.onload = function () { window.print(); };
    </script>
</body>
</html>
