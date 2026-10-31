{{--
    Boton para activar los avisos de resultados.

    Solo se muestra si hay claves configuradas y si el navegador sabe
    manejar avisos. En iPhone, Safari solo los entrega cuando el sitio
    esta agregado a la pantalla de inicio, asi que a esos se les explica
    el paso en vez de ofrecerles un boton que no les va a funcionar.
--}}
@if ($pushDisponible)
    <div class="card avisos" id="caja-avisos" hidden>
        <div class="avisos-texto">
            <strong>&#128276; Avisos de resultados</strong>
            <p id="avisos-detalle">Activa los avisos y te llega una notificacion apenas se registre un resultado.</p>
        </div>
        <button type="button" class="btn-aviso" id="boton-avisos" hidden>Activar avisos</button>
    </div>

    <style>
        .avisos {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            flex-wrap: wrap;
        }
        .avisos-texto strong { color: var(--azul-oscuro); font-size: 15px; }
        .avisos-texto p { margin: 4px 0 0; font-size: 13px; color: #666; }
        .btn-aviso {
            background: var(--azul-claro);
            color: var(--blanco);
            border: 0;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 14px;
            font-family: inherit;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-aviso:hover { background: var(--azul-oscuro); }
        .btn-aviso[disabled] { opacity: .6; cursor: default; }

        @media (max-width: 560px) {
            .avisos { flex-direction: column; align-items: stretch; }
            .btn-aviso { width: 100%; }
        }
    </style>

    <script>
    (function () {
        var CLAVE_PUBLICA = @json($pushClavePublica);
        var caja = document.getElementById('caja-avisos');
        var boton = document.getElementById('boton-avisos');
        var detalle = document.getElementById('avisos-detalle');
        var token = document.querySelector('meta[name="csrf-token"]');

        // En iPhone y iPad solo llegan avisos si el sitio esta instalado en
        // la pantalla de inicio. Si no lo esta, se explica el paso.
        var esApple = /iPad|iPhone|iPod/.test(navigator.userAgent);
        var instalado = window.navigator.standalone === true
            || window.matchMedia('(display-mode: standalone)').matches;

        if (esApple && !instalado) {
            caja.hidden = false;
            detalle.innerHTML = 'Para recibir avisos en iPhone, toca el boton de compartir '
                + 'y elige <strong>"Agregar a pantalla de inicio"</strong>. Abre el sitio desde '
                + 'ese icono y veras aqui el boton para activarlos.';

            return;
        }

        var soportado = 'serviceWorker' in navigator
            && 'PushManager' in window
            && 'Notification' in window;

        if (!soportado || !CLAVE_PUBLICA || !token) {
            return;
        }

        caja.hidden = false;
        boton.hidden = false;

        // La clave viaja en base64 "de url" y el navegador la pide en bytes.
        function aBytes(base64) {
            var relleno = '='.repeat((4 - (base64.length % 4)) % 4);
            var normal = (base64 + relleno).replace(/-/g, '+').replace(/_/g, '/');
            var crudo = window.atob(normal);
            var bytes = new Uint8Array(crudo.length);

            for (var i = 0; i < crudo.length; i++) {
                bytes[i] = crudo.charCodeAt(i);
            }

            return bytes;
        }

        function avisar(texto, deshabilitar) {
            detalle.textContent = texto;
            boton.disabled = !!deshabilitar;
        }

        function enviar(ruta, cuerpo) {
            return fetch(ruta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token.content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(cuerpo),
            });
        }

        function pintarComoActivo() {
            boton.textContent = 'Desactivar avisos';
            boton.disabled = false;
            detalle.textContent = 'Los avisos estan activos en este dispositivo.';
        }

        function pintarComoInactivo() {
            boton.textContent = 'Activar avisos';
            boton.disabled = false;
            detalle.textContent = 'Activa los avisos y te llega una notificacion apenas se registre un resultado.';
        }

        var registro = null;

        navigator.serviceWorker.register('/sw.js').then(function (reg) {
            registro = reg;

            return reg.pushManager.getSubscription();
        }).then(function (suscripcion) {
            if (suscripcion && Notification.permission === 'granted') {
                pintarComoActivo();
            } else {
                pintarComoInactivo();
            }
        }).catch(function () {
            caja.hidden = true;
        });

        boton.addEventListener('click', function () {
            if (!registro) {
                return;
            }

            boton.disabled = true;

            registro.pushManager.getSubscription().then(function (suscripcion) {
                // Ya estaba activo: se da de baja.
                if (suscripcion) {
                    var direccion = suscripcion.endpoint;

                    return suscripcion.unsubscribe().then(function () {
                        return enviar(@json(route('avisos.desuscribir')), { endpoint: direccion });
                    }).then(pintarComoInactivo);
                }

                if (Notification.permission === 'denied') {
                    avisar('Los avisos estan bloqueados para este sitio. Habilitalos desde la configuracion del navegador.', true);

                    return;
                }

                return registro.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: aBytes(CLAVE_PUBLICA),
                }).then(function (nueva) {
                    var datos = nueva.toJSON();

                    return enviar(@json(route('avisos.suscribir')), {
                        endpoint: datos.endpoint,
                        clave_p256dh: datos.keys.p256dh,
                        clave_auth: datos.keys.auth,
                    }).then(function (respuesta) {
                        if (!respuesta.ok) {
                            throw new Error('rechazado');
                        }

                        pintarComoActivo();
                    });
                });
            }).catch(function () {
                avisar('No se pudieron activar los avisos. Intentalo de nuevo.', false);
                boton.disabled = false;
            });
        });
    })();
    </script>
@endif
