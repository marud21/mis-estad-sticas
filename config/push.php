<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Claves VAPID
    |--------------------------------------------------------------------------
    |
    | Identifican a este servidor ante los servicios de notificaciones de los
    | navegadores (Google, Mozilla, Apple). Se generan una sola vez con
    | "php artisan push:claves" y van en el .env. La publica viaja al
    | navegador; la privada no sale nunca del servidor.
    |
    | Si faltan, el sistema sigue funcionando igual pero sin avisos: no se
    | ofrece activarlos y no se intenta enviarlos.
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', env('APP_URL', 'https://misestadisticas.online')),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

];
