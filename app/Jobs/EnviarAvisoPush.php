<?php

namespace App\Jobs;

use App\Services\NotificacionPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Manda el aviso por fuera de la peticion que lo origino.
 *
 * Avisar puede tardar unos segundos (es una llamada por cada navegador
 * suscrito), y no tiene por que hacer esperar a quien esta registrando un
 * resultado. Si falla, se reintenta, pero nunca se le echa atras el
 * resultado a nadie por culpa de un aviso.
 */
class EnviarAvisoPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        private readonly string $titulo,
        private readonly string $cuerpo,
        private readonly string $url,
        /**
         * Cuando es cierto el aviso va solo a los navegadores de quien
         * tiene sesion en el sistema. Se usa para lo interno, que nombra
         * socios y deudas y no puede salir hacia la pagina publica.
         */
        private readonly bool $soloAdministracion = false,
    ) {
    }

    public function handle(NotificacionPushService $push): void
    {
        $this->soloAdministracion
            ? $push->enviarALaAdministracion($this->titulo, $this->cuerpo, $this->url)
            : $push->enviarATodos($this->titulo, $this->cuerpo, $this->url);
    }
}
