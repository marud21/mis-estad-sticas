<?php

namespace App\Services;

use App\Models\SuscripcionPush;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envia los avisos a los navegadores que los aceptaron.
 *
 * No hay cuentas ni destinatarios: cada navegador que acepto dejo una
 * direccion, y se le manda a todas. Las que el servicio del navegador
 * reporta como muertas (el usuario desinstalo, limpio el navegador o
 * revoco el permiso) se borran solas, para que la lista no se llene de
 * direcciones que ya no existen.
 */
class NotificacionPushService
{
    /** Codigos con los que el servicio del navegador dice "esta ya no existe". */
    private const CADUCADAS = [404, 410];

    /** Sin claves configuradas no se ofrece ni se intenta enviar nada. */
    public function estaConfigurado(): bool
    {
        return filled(config('push.vapid.public_key'))
            && filled(config('push.vapid.private_key'));
    }

    public function clavePublica(): ?string
    {
        return config('push.vapid.public_key');
    }

    /** Guarda (o actualiza) la suscripcion de un navegador. */
    public function suscribir(array $datos, ?string $navegador = null): SuscripcionPush
    {
        return SuscripcionPush::updateOrCreate(
            ['endpoint_huella' => SuscripcionPush::huella($datos['endpoint'])],
            [
                'endpoint' => $datos['endpoint'],
                'clave_p256dh' => $datos['clave_p256dh'],
                'clave_auth' => $datos['clave_auth'],
                'navegador' => $navegador ? substr($navegador, 0, 255) : null,
            ],
        );
    }

    public function desuscribir(string $endpoint): void
    {
        SuscripcionPush::where('endpoint_huella', SuscripcionPush::huella($endpoint))->delete();
    }

    /**
     * Manda el aviso a todos los navegadores suscritos.
     *
     * @return array{enviados:int, caducados:int, fallidos:int}
     */
    public function enviarATodos(string $titulo, string $cuerpo, string $url): array
    {
        $resumen = ['enviados' => 0, 'caducados' => 0, 'fallidos' => 0];

        if (! $this->estaConfigurado()) {
            Log::warning('Aviso push no enviado: faltan las claves VAPID.');

            return $resumen;
        }

        $suscripciones = SuscripcionPush::all();

        if ($suscripciones->isEmpty()) {
            return $resumen;
        }

        $webPush = new WebPush(['VAPID' => [
            'subject' => config('push.vapid.subject'),
            'publicKey' => config('push.vapid.public_key'),
            'privateKey' => config('push.vapid.private_key'),
        ]]);

        $carga = json_encode([
            'titulo' => $titulo,
            'cuerpo' => $cuerpo,
            'url' => $url,
        ], JSON_UNESCAPED_UNICODE);

        foreach ($suscripciones as $suscripcion) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $suscripcion->endpoint,
                    'publicKey' => $suscripcion->clave_p256dh,
                    'authToken' => $suscripcion->clave_auth,
                ]),
                $carga,
            );
        }

        $caducadas = [];

        foreach ($webPush->flush() as $reporte) {
            if ($reporte->isSuccess()) {
                $resumen['enviados']++;

                continue;
            }

            $codigo = $reporte->getResponse()?->getStatusCode();

            if (in_array($codigo, self::CADUCADAS, true)) {
                $caducadas[] = SuscripcionPush::huella($reporte->getEndpoint());
                $resumen['caducados']++;

                continue;
            }

            $resumen['fallidos']++;
            Log::warning('Aviso push rechazado.', [
                'codigo' => $codigo,
                'motivo' => $reporte->getReason(),
            ]);
        }

        if ($caducadas) {
            SuscripcionPush::whereIn('endpoint_huella', $caducadas)->delete();
        }

        return $resumen;
    }
}
