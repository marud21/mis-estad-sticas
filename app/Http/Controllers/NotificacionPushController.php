<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuscripcionPushRequest;
use App\Services\NotificacionPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificacionPushController extends Controller
{
    public function __construct(private readonly NotificacionPushService $push)
    {
    }

    /** Guarda el permiso que acaba de dar un navegador. */
    public function suscribir(SuscripcionPushRequest $request): JsonResponse
    {
        if (! $this->push->estaConfigurado()) {
            return response()->json(['mensaje' => 'Los avisos no estan disponibles por ahora.'], 503);
        }

        $this->push->suscribir($request->validated(), $request->userAgent());

        return response()->json(['mensaje' => 'Listo, te avisaremos cuando haya un resultado nuevo.']);
    }

    /** El navegador avisa que ya no quiere recibir nada. */
    public function desuscribir(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'endpoint' => ['required', 'string', 'max:1000'],
        ]);

        $this->push->desuscribir($datos['endpoint']);

        return response()->json(['mensaje' => 'Dejaras de recibir avisos.']);
    }
}
