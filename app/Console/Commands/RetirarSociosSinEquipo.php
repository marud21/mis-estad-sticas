<?php

namespace App\Console\Commands;

use App\Models\Socio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RetirarSociosSinEquipo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:retirar-socios-sin-equipo {--dry-run : Solo muestra que haria, sin guardar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marca como retirados a los socios activos que no tienen ningun equipo registrado, para que dejen de entrar en los cobros masivos. Los suspendidos y excluidos no se tocan, ni tampoco los cargos o pagos de nadie.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $soloSimular = $this->option('dry-run');

        $socios = Socio::doesntHave('equipos')
            ->where('estado', Socio::ESTADO_ACTIVO)
            ->orderBy('nombre_completo')
            ->get();

        if ($socios->isEmpty()) {
            $this->info('No hay socios activos sin equipo. No hay nada que hacer.');

            return self::SUCCESS;
        }

        foreach ($socios as $socio) {
            $this->line("{$socio->nombre_completo} (doc. {$socio->numero_documento}) -> retirado");
        }

        if (! $soloSimular) {
            DB::transaction(function () use ($socios) {
                Socio::whereIn('id', $socios->pluck('id'))->update([
                    'estado' => Socio::ESTADO_RETIRADO,
                    'fecha_cambio_estado' => today(),
                    'suspendido_por_equipo' => false,
                ]);
            });
        }

        $this->newLine();
        $this->info(($soloSimular ? '[SIMULACION] ' : '')."Socios marcados como retirados: {$socios->count()}.");

        // Contexto de lo que se deja igual a proposito.
        $this->line('Sin tocar (conservan su estado): '
            .Socio::doesntHave('equipos')->where('estado', Socio::ESTADO_SUSPENDIDO)->count().' suspendido(s), '
            .Socio::doesntHave('equipos')->where('estado', Socio::ESTADO_EXCLUIDO)->count().' excluido(s), '
            .Socio::doesntHave('equipos')->where('estado', Socio::ESTADO_RETIRADO)->count().' ya retirado(s).');
        $this->line('No se modifico ningun cargo ni pago.');

        return self::SUCCESS;
    }
}
