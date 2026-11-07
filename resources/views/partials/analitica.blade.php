{{--
    Medicion de visitas con Google Analytics.

    Va solo en las paginas publicas: medir las entradas al panel de
    administracion ensuciaria las cifras con el uso propio.

    Si no hay identificador configurado no se carga nada. Es a proposito:
    en local y en cualquier copia del sistema no se mide ni se envia nada
    a Google.
--}}
@php($gaId = config('analitica.google_id'))

@if (filled($gaId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($gaId), {
            // No se guarda la direccion IP completa del visitante. Google
            // la recorta antes de registrarla.
            anonymize_ip: true
        });
    </script>
@endif
