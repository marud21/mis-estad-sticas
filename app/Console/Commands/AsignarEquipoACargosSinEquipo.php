<?php

namespace App\Console\Commands;

use App\Models\Cargo;
use App\Models\Socio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AsignarEquipoACargosSinEquipo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:asignar-equipo-a-cargos-sin-equipo {--dry-run : Solo muestra que haria, sin guardar cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asigna al equipo (y su torneo) los cargos sin equipo de los socios que tienen exactamente un equipo registrado. Socios sin equipo o con varios se dejan igual.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $soloSimular = $this->option('dry-run');

        $socios = Socio::whereHas('cargos', fn ($q) => $q->whereNull('equipo_id'))
            ->with('equipos')
            ->get();

        $socioActualizados = 0;
        $cargosActualizados = 0;
        $sinEquipo = 0;
        $conVariosEquipos = 0;

        foreach ($socios as $socio) {
            $cantidadEquipos = $socio->equipos->count();

            if ($cantidadEquipos === 0) {
                $sinEquipo++;

                continue;
            }

            if ($cantidadEquipos > 1) {
                $conVariosEquipos++;

                continue;
            }

            $equipo = $socio->equipos->first();

            $cargosDelSocio = Cargo::where('socio_id', $socio->id)->whereNull('equipo_id')->get();

            $this->line("{$socio->nombre_completo}: {$cargosDelSocio->count()} cargo(s) -> equipo \"{$equipo->nombre}\"");

            if (! $soloSimular) {
                DB::table('cargos')
                    ->where('socio_id', $socio->id)
                    ->whereNull('equipo_id')
                    ->update(['equipo_id' => $equipo->id, 'torneo_id' => $equipo->torneo_id]);
            }

            $socioActualizados++;
            $cargosActualizados += $cargosDelSocio->count();
        }

        $this->newLine();
        $this->info(($soloSimular ? '[SIMULACION] ' : '')."Socios actualizados: {$socioActualizados} ({$cargosActualizados} cargo(s) en total).");
        $this->line("Socios sin equipo (sin tocar): {$sinEquipo}");
        $this->line("Socios con mas de un equipo (sin tocar): {$conVariosEquipos}");

        return self::SUCCESS;
    }
}
