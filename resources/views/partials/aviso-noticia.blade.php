{{--
    Ventana con la ultima noticia, para que no pase desapercibida.

    Sale sola al entrar a la portada. A cada visitante le sale una vez:
    si la cierra, esa noticia no le vuelve a saltar. Y pasados unos dias
    (Noticia::DIAS_DESTACADA) deja de saltar para todos, aunque siga
    siendo la ultima publicada: una ventana con una noticia vieja no
    avisa de nada y solo estorba.

    Nace escondida y la muestra el javascript. Al reves —pintarla
    visible y esconderla despues— el visitante alcanza a verla un
    instante aunque ya la hubiera cerrado antes.
--}}
@if (! empty($noticiaDestacada))
    @php
        $noticiaUrl = route('noticias-publicas.show', $noticiaDestacada);
        $noticiaImagen = $noticiaDestacada->imagen_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($noticiaDestacada->imagen_path)
            : null;
        // Con imagen el resumen va mas corto: el afiche ya cuenta de que
        // se trata, y un parrafo largo empujaria los botones fuera de la
        // pantalla en un telefono.
        $noticiaResumen = \Illuminate\Support\Str::limit(
            trim(strip_tags($noticiaDestacada->contenido)), $noticiaImagen ? 130 : 240,
        );
    @endphp

    <div class="aviso-noticia" id="aviso-noticia" data-noticia="{{ $noticiaDestacada->id }}" hidden>
        <div class="aviso-noticia-fondo" data-cerrar-noticia></div>

        <div class="aviso-noticia-caja" role="dialog" aria-modal="true"
             aria-labelledby="aviso-noticia-titulo">
            <button type="button" class="aviso-noticia-cerrar" data-cerrar-noticia
                    aria-label="Cerrar">&times;</button>

            <div class="aviso-noticia-rotulo">Ultima noticia</div>

            @if ($noticiaImagen)
                {{-- La imagen va entera y enlazada. Muchas noticias del
                     club son afiches con el texto repartido de arriba
                     abajo: recortarlos para rellenar un recuadro se come
                     justamente lo que dice de que se trata. --}}
                <a class="aviso-noticia-marco" href="{{ $noticiaUrl }}"
                   aria-label="Ver la noticia completa">
                    <img class="aviso-noticia-imagen" src="{{ $noticiaImagen }}"
                         alt="{{ $noticiaDestacada->titulo }}">
                </a>
            @endif

            <div class="aviso-noticia-cuerpo">
                <h2 id="aviso-noticia-titulo">{{ $noticiaDestacada->titulo }}</h2>
                <div class="aviso-noticia-fecha">
                    {{ $noticiaDestacada->fecha_publicacion->format('d/m/Y') }}
                </div>

                @if ($noticiaResumen !== '')
                    <p>{{ $noticiaResumen }}</p>
                @endif

                <div class="aviso-noticia-acciones">
                    <a class="aviso-noticia-leer" href="{{ $noticiaUrl }}">Leer la noticia</a>
                    <button type="button" class="aviso-noticia-despues" data-cerrar-noticia>
                        Ahora no
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .aviso-noticia {
            position: fixed;
            inset: 0;
            /* Por encima del aviso de cookies (60), que es lo unico mas
               que se superpone en esta pagina. */
            z-index: 80;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 16px;
        }
        /* Hace falta aparte: el "display: flex" de arriba le gana al
           [hidden] del navegador y la ventana no se esconderia nunca. */
        .aviso-noticia[hidden] { display: none; }

        .aviso-noticia-fondo {
            position: absolute;
            inset: 0;
            background: rgba(0, 24, 10, .55);
        }

        .aviso-noticia-caja {
            position: relative;
            width: 100%;
            max-width: 440px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
            background: var(--blanco, #fff);
            border-radius: 10px;
            border-top: 4px solid var(--acento, #f9f002);
            box-shadow: 0 18px 50px rgba(0, 0, 0, .35);
        }

        .aviso-noticia-cerrar {
            position: absolute;
            top: 6px; right: 8px;
            z-index: 2;
            width: 34px; height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .9);
            color: var(--marca-oscura, #004d26);
            border: 0;
            border-radius: 50%;
            font-size: 26px;
            line-height: 1;
            font-family: inherit;
            cursor: pointer;
        }
        .aviso-noticia-cerrar:hover { background: #fff; }

        .aviso-noticia-rotulo {
            padding: 12px 18px 0;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--marca, #006d36);
        }

        .aviso-noticia-marco {
            display: block;
            margin-top: 10px;
            padding: 8px;
            /* Un fondo tenue detras: con un afiche vertical queda aire a
               los lados, y asi se ve a proposito y no como un hueco. */
            background: var(--gris-claro, #f2f6f3);
            border-top: 1px solid var(--gris-borde, #d9e3dc);
            border-bottom: 1px solid var(--gris-borde, #d9e3dc);
            text-align: center;
        }
        /*
         * Entera y sin deformar. No se pone "width: 100%" junto al tope
         * de alto: con los dos a la vez el navegador estira la imagen.
         * Con los maximos y las medidas en automatico, se encoge lo justo
         * para caber y conserva su proporcion.
         */
        .aviso-noticia-imagen {
            display: inline-block;
            max-width: 100%;
            max-height: 340px;
            width: auto;
            height: auto;
            vertical-align: middle;
        }

        .aviso-noticia-cuerpo { padding: 14px 18px 18px; }
        .aviso-noticia-cuerpo h2 {
            margin: 0 0 4px;
            font-size: 20px;
            line-height: 1.25;
            color: var(--marca-oscura, #004d26);
        }
        .aviso-noticia-fecha {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        .aviso-noticia-cuerpo p {
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }

        .aviso-noticia-acciones { display: flex; gap: 10px; flex-wrap: wrap; }
        .aviso-noticia-leer {
            flex: 1 1 auto;
            text-align: center;
            background: var(--marca, #006d36);
            color: #fff;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        .aviso-noticia-leer:hover { background: var(--marca-oscura, #004d26); }
        .aviso-noticia-despues {
            flex: none;
            background: none;
            color: #555;
            border: 1px solid var(--gris-borde, #d9e3dc);
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
        }
        .aviso-noticia-despues:hover { background: var(--gris-claro, #f2f6f3); }

        @media (max-width: 480px) {
            .aviso-noticia { padding: 12px; }
            /* En el telefono la imagen cede altura para que el titulo y
               los botones sigan cabiendo sin tener que desplazar. */
            .aviso-noticia-imagen { max-height: 230px; }
            .aviso-noticia-cuerpo h2 { font-size: 18px; }
            .aviso-noticia-acciones { flex-direction: column-reverse; }
            .aviso-noticia-despues { width: 100%; }
        }
    </style>

    <script>
    (function () {
        var caja = document.getElementById('aviso-noticia');
        if (!caja) {
            return;
        }

        var LLAVE = 'noticia-vista-' + caja.dataset.noticia;

        /*
         * En navegacion privada o con el almacenamiento bloqueado, leer o
         * escribir puede fallar. Si pasa, la ventana sale igual: es
         * preferible que se repita a que no se vea nunca.
         */
        function yaLaVio() {
            try {
                return window.localStorage.getItem(LLAVE) === '1';
            } catch (e) {
                return false;
            }
        }

        function recordar() {
            try {
                window.localStorage.setItem(LLAVE, '1');
            } catch (e) {
                // Sin memoria: le volvera a salir la proxima vez.
            }
        }

        var antesDelFoco = null;

        function abrir() {
            antesDelFoco = document.activeElement;
            caja.hidden = false;
            var cerrar = caja.querySelector('.aviso-noticia-cerrar');
            if (cerrar) {
                cerrar.focus();
            }
        }

        function cerrar() {
            caja.hidden = true;
            recordar();
            if (antesDelFoco && typeof antesDelFoco.focus === 'function') {
                antesDelFoco.focus();
            }
        }

        caja.querySelectorAll('[data-cerrar-noticia]').forEach(function (boton) {
            boton.addEventListener('click', cerrar);
        });

        /*
         * Entrar a la noticia tambien cuenta como haberla visto: no tiene
         * sentido que le vuelva a saltar a quien ya fue a leerla. Se
         * toman TODOS los caminos hacia la noticia, no solo el boton: la
         * imagen tambien lleva alla.
         */
        caja.querySelectorAll('.aviso-noticia-leer, .aviso-noticia-marco')
            .forEach(function (enlace) {
                enlace.addEventListener('click', recordar);
            });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !caja.hidden) {
                cerrar();
            }
        });

        if (yaLaVio()) {
            return;
        }

        /*
         * El aviso de cookies tambien se superpone. Si esta a la vista se
         * espera a que lo cierren, porque dos ventanas encima de la otra
         * es una mala forma de recibir a alguien que entra por primera
         * vez. Si no hay aviso de cookies, se abre tras un momento, para
         * que la pagina alcance a pintarse primero.
         */
        var cookies = document.getElementById('aviso-cookies');
        var botonCookies = document.getElementById('aviso-cookies-ok');

        if (cookies && !cookies.hidden && botonCookies) {
            botonCookies.addEventListener('click', function () {
                window.setTimeout(abrir, 400);
            });

            return;
        }

        window.setTimeout(abrir, 700);
    })();
    </script>
@endif
