<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->appendToGroup('web', \App\Http\Middleware\CerrarSesionPorInactividad::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /**
         * Cuando la sesion vence por seguridad, el token del formulario deja
         * de ser valido y Laravel responde con la pantalla "419 Page Expired",
         * que no le dice nada al usuario ni le ofrece salida. En vez de eso
         * lo mandamos al login con un mensaje claro para que vuelva a entrar.
         *
         * Las peticiones que esperan JSON (los botones de pagos multiples y
         * cobro de tarjetas) reciben el aviso como dato, para que la pantalla
         * pueda mostrarlo sin perder lo que el usuario ya habia escrito.
         */
        $exceptions->render(function (HttpException $e, Request $request) {
            // Laravel convierte el token vencido (TokenMismatchException) en
            // un HttpException 419 antes de llegar aqui, asi que se reconoce
            // por el codigo. Cualquier otro error sigue su curso normal.
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            $mensaje = 'Tu sesion expiro por seguridad. Ingresa nuevamente para continuar.';

            if ($request->expectsJson()) {
                return response()->json([
                    'sesion_expirada' => true,
                    'mensaje' => $mensaje,
                    'login_url' => route('login'),
                ], 419);
            }

            return redirect()->route('login')->with('status', $mensaje);
        });
    })->create();
