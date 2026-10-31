/*
 * Trabajador en segundo plano.
 *
 * El navegador lo mantiene vivo aunque la pagina este cerrada, y es el
 * unico que puede mostrar un aviso del sistema. Por eso vive en la raiz
 * del sitio: desde ahi alcanza a todas las paginas.
 */

self.addEventListener('install', function () {
    // Entra en servicio de una vez, sin esperar a que cierren las pestañas
    // viejas, para que el primer aviso no se pierda.
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', function (event) {
    var datos = { titulo: 'Mis Estadisticas', cuerpo: 'Hay novedades.', url: '/' };

    if (event.data) {
        try {
            datos = Object.assign(datos, event.data.json());
        } catch (e) {
            datos.cuerpo = event.data.text();
        }
    }

    event.waitUntil(
        self.registration.showNotification(datos.titulo, {
            body: datos.cuerpo,
            icon: '/favicon-192.png',
            badge: '/favicon-192.png',
            data: { url: datos.url },
            // Varios resultados seguidos se apilan como avisos distintos en
            // vez de pisarse unos a otros.
            tag: 'resultado-' + Date.now(),
        })
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var destino = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (ventanas) {
            // Si el sitio ya esta abierto se reutiliza esa pestaña en vez de
            // abrir otra mas.
            for (var i = 0; i < ventanas.length; i++) {
                if (ventanas[i].url.indexOf(self.location.origin) === 0 && 'focus' in ventanas[i]) {
                    ventanas[i].navigate(destino);

                    return ventanas[i].focus();
                }
            }

            return self.clients.openWindow(destino);
        })
    );
});
