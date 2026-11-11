<?php

namespace App\Http\Middleware;

use App\Support\Permisos;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cierra las pantallas que el rol no tiene permitidas.
 *
 * Va sobre todo el grupo privado, no ruta por ruta: asi una pantalla
 * nueva nace cerrada para los roles limitados y hay que abrirla a
 * proposito. Esconder el menu no alcanza, porque la direccion se puede
 * escribir a mano.
 */
class VerificarPermisos
{
    public function handle(Request $request, Closure $next): Response
    {
        $ruta = $request->route()?->getName();

        if (Permisos::puede($request->user(), $ruta)) {
            return $next($request);
        }

        $mensaje = 'Tu usuario no tiene acceso a esa seccion.';

        if ($request->expectsJson()) {
            return response()->json(['mensaje' => $mensaje], 403);
        }

        // Se devuelve a su pantalla de inicio con el aviso, en vez de una
        // pantalla de error: el usuario no hizo nada malo, simplemente no
        // le corresponde esa seccion.
        return redirect()
            ->to($request->user()?->pantallaDeInicio() ?? route('login'))
            ->with('status', $mensaje);
    }
}
