<?php

namespace App\Console\Commands;

use App\Jobs\EnviarAvisoPush;
use App\Services\SocioInactivoService;
use Illuminate\Console\Command;

/**
 * Avisa a la administracion que hay socios para revisar.
 *
 * Va programado una vez por semana y no todos los dias a proposito: la
 * lista se mueve despacio, y un aviso diario con el mismo contenido deja
 * de leerse a la semana.
 */
class AvisarSociosSinPago extends Command
{
    protected $signature = 'avisos:socios-sin-pago
        {--meses= : Otro corte en meses, en vez del normal}
        {--dry-run : Solo dice que mandaria, sin mandar nada}';

    protected $description = 'Revisa cuantos socios activos o en sostenimiento llevan varios meses sin pagar y manda un aviso push a la administracion. No cambia ningun dato.';

    public function handle(SocioInactivoService $inactivos): int
    {
        $meses = $this->option('meses') !== null
            ? (int) $this->option('meses')
            : SocioInactivoService::MESES_LIMITE;

        $resumen = $inactivos->resumen($meses);
        $cuantos = $resumen['total'];

        if ($cuantos === 0) {
            $this->info("Ningun socio lleva {$meses} meses o mas sin pagar. No se manda nada.");

            return self::SUCCESS;
        }

        $titulo = $cuantos === 1
            ? 'Hay 1 socio para revisar'
            : "Hay {$cuantos} socios para revisar";

        $deuda = number_format($resumen['deuda'], 0, ',', '.');
        $cuerpo = $cuantos === 1
            ? "Lleva {$meses} meses o mas sin pagar y debe \${$deuda}."
            : "Llevan {$meses} meses o mas sin pagar y entre todos deben \${$deuda}.";

        $url = route('socios-inactivos.index');

        if ($this->option('dry-run')) {
            $this->line('Se mandaria (solo a la administracion):');
            $this->line("  titulo: {$titulo}");
            $this->line("  cuerpo: {$cuerpo}");
            $this->line("  enlace: {$url}");

            return self::SUCCESS;
        }

        // Va por la cola, igual que el aviso de resultados: mandar tarda
        // una llamada por navegador y esto corre desde el programador.
        EnviarAvisoPush::dispatch($titulo, $cuerpo, $url, soloAdministracion: true);

        $this->info("Aviso encolado: {$cuantos} socio(s) para revisar.");

        return self::SUCCESS;
    }
}
