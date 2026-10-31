<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

class GenerarClavesPush extends Command
{
    protected $signature = 'push:claves';

    protected $description = 'Genera el par de claves VAPID para las notificaciones';

    public function handle(): int
    {
        try {
            $claves = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            $this->error('No se pudieron generar las claves: '.$e->getMessage());
            $this->line('Suele ser que openssl no encuentra su archivo de configuracion.');

            return self::FAILURE;
        }

        $this->info('Copia estas dos lineas al archivo .env:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$claves['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$claves['privateKey']);
        $this->newLine();
        $this->warn('La clave privada no debe compartirse ni subirse a Git.');

        return self::SUCCESS;
    }
}
