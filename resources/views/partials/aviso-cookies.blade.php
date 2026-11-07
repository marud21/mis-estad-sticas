{{--
    Aviso de cookies.

    Google Analytics usa cookies, y en Colombia la Ley 1581 de 2012 pide
    informarle al visitante que se recogen datos suyos. Esto es un aviso:
    informa y se cierra. No bloquea la medicion hasta que acepten, que es
    lo que exigiria la norma europea si alguna vez hace falta.

    Solo aparece si hay analitica configurada, y una vez cerrado no vuelve
    a salir en ese navegador.
--}}
@if (filled(config('analitica.google_id')) && config('analitica.avisar_cookies'))
    <div class="aviso-cookies" id="aviso-cookies" hidden>
        <p>
            Este sitio usa cookies para contar las visitas y entender que secciones
            se consultan mas. No se recoge informacion que permita identificarte.
        </p>
        <button type="button" id="aviso-cookies-ok">Entendido</button>
    </div>

    <style>
        .aviso-cookies {
            position: fixed;
            left: 12px; right: 12px; bottom: 12px;
            z-index: 60;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            max-width: 900px;
            margin: 0 auto;
            padding: 14px 16px;
            background: var(--azul-oscuro, #0b2545);
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, .22);
        }
        .aviso-cookies p { margin: 0; font-size: 13px; line-height: 1.45; flex: 1 1 260px; }
        .aviso-cookies button {
            flex: none;
            background: #fff;
            color: var(--azul-oscuro, #0b2545);
            border: 0;
            border-radius: 6px;
            padding: 9px 18px;
            font-size: 13px;
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
        }
        .aviso-cookies button:hover { background: #e6ecf3; }

        @media (max-width: 480px) {
            .aviso-cookies { flex-direction: column; align-items: stretch; gap: 10px; }
            /* Sin esto el parrafo se estira para llenar la columna y el
               aviso termina ocupando media pantalla. */
            .aviso-cookies p { flex: 0 0 auto; }
            .aviso-cookies button { width: 100%; }
        }
    </style>

    <script>
    (function () {
        var caja = document.getElementById('aviso-cookies');
        var boton = document.getElementById('aviso-cookies-ok');
        var LLAVE = 'aviso-cookies-visto';

        // En navegacion privada o con el almacenamiento bloqueado, leer o
        // escribir puede fallar. Si pasa, se muestra el aviso igual: es
        // preferible repetirlo a no mostrarlo nunca.
        function visto() {
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
                // Sin memoria: el aviso volvera a salir la proxima vez.
            }
        }

        if (!visto()) {
            caja.hidden = false;
        }

        boton.addEventListener('click', function () {
            caja.hidden = true;
            recordar();
        });
    })();
    </script>
@endif
