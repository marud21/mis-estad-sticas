<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Aviso semanal de socios sin pagos recientes.
 *
 * Los lunes a las 8 de la mañana, hora de Bogota, que es cuando alguien
 * se va a sentar a mirarlo. Una vez por semana y no a diario porque la
 * lista se mueve despacio: un aviso diario con el mismo contenido deja
 * de leerse a la semana.
 *
 * withoutOverlapping evita que dos corridas se pisen si el servidor
 * viene lento, y onOneServer lo deja listo por si algun dia hay mas de
 * una maquina.
 */
Schedule::command('avisos:socios-sin-pago')
    ->weeklyOn(1, '08:00')
    ->timezone('America/Bogota')
    ->withoutOverlapping()
    ->onOneServer();
