<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Analytics
    |--------------------------------------------------------------------------
    |
    | Identificador de medicion de Google Analytics 4, con la forma
    | G-XXXXXXXXXX. Se saca de la propiedad en analytics.google.com.
    |
    | Si esta vacio no se carga nada: ni el script de Google ni el aviso de
    | cookies. Asi el sistema funciona igual en local y en cualquier copia
    | donde no se quiera medir.
    |
    */

    'google_id' => env('GA_MEASUREMENT_ID'),

    /*
    | El aviso de cookies acompaña a Google Analytics, que si las usa. Se
    | puede apagar aparte si algun dia se cambia por una herramienta que no
    | las necesite.
    */

    'avisar_cookies' => env('ANALITICA_AVISO_COOKIES', true),

];
