<?php

namespace App\Providers;

use App\Models\Configuracion;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        View::composer(['layouts.app', 'home', 'auth.login', 'consulta.index', 'pagos.recibo'], function ($view) {
            $logoPath = Configuracion::obtener(Configuracion::LOGO_PATH);

            $view->with([
                'nombreSistema' => Configuracion::obtener(Configuracion::NOMBRE_SISTEMA, Configuracion::NOMBRE_SISTEMA_DEFECTO),
                'logoUrl' => $logoPath ? Storage::disk('public')->url($logoPath) : null,
                'descripcionPortada' => Configuracion::obtener(Configuracion::DESCRIPCION_PORTADA, Configuracion::DESCRIPCION_PORTADA_DEFECTO),
            ]);
        });
    }
}
