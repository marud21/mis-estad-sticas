<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CerrarSesionPorInactividad
{
    /**
     * Minutos de inactividad permitidos antes de forzar el cierre de sesion.
     */
    private const MINUTOS_LIMITE = 20;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $ultimaActividad = $request->session()->get('ultima_actividad');

            if ($ultimaActividad && now()->diffInMinutes($ultimaActividad, absolute: true) >= self::MINUTOS_LIMITE) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->with('status', 'Tu sesion se cerro por inactividad. Ingresa nuevamente.');
            }

            $request->session()->put('ultima_actividad', now());
        }

        return $next($request);
    }
}
