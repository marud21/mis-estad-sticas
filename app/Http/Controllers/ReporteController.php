<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Socio;
use App\Services\ReporteService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteService $reportes,
        private readonly WhatsAppService $whatsapp,
    ) {
    }

    public function socio(Socio $socio)
    {
        return $this->reportes->socioPdf($socio)->download("socio-{$socio->numero_documento}.pdf");
    }

    public function equipo(Equipo $equipo)
    {
        return $this->reportes->equipoPdf($equipo)->download("equipo-{$equipo->id}.pdf");
    }

    /**
     * Genera el reporte PDF de varios equipos a la vez y los entrega
     * empaquetados en un unico archivo ZIP (un PDF por equipo).
     */
    public function equiposMultiples(Request $request)
    {
        $request->validate([
            'equipo_ids' => ['required', 'array', 'min:1'],
            'equipo_ids.*' => ['exists:equipos,id'],
        ]);

        $equipos = Equipo::whereIn('id', $request->input('equipo_ids'))->orderBy('nombre')->get();

        $rutaZip = storage_path('app/temp/reportes-equipos-'.uniqid().'.zip');
        if (! is_dir(dirname($rutaZip))) {
            mkdir(dirname($rutaZip), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($rutaZip, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $nombresUsados = [];
        foreach ($equipos as $equipo) {
            $contenidoPdf = $this->reportes->equipoPdf($equipo)->output();

            $nombreBase = Str::slug($equipo->nombre) ?: "equipo-{$equipo->id}";
            $nombreArchivo = "{$nombreBase}.pdf";
            $contador = 1;
            while (in_array($nombreArchivo, $nombresUsados, true)) {
                $nombreArchivo = "{$nombreBase}-{$contador}.pdf";
                $contador++;
            }
            $nombresUsados[] = $nombreArchivo;

            $zip->addFromString($nombreArchivo, $contenidoPdf);
        }

        $zip->close();

        return response()->download($rutaZip, 'reportes-equipos.zip')->deleteFileAfterSend(true);
    }

    public function planillaPagos(Equipo $equipo)
    {
        return $this->reportes->planillaPagosPdf($equipo)->download("planilla-de-pagos-{$equipo->nombre}.pdf");
    }

    public function whatsappSocio(Socio $socio)
    {
        $socio->load(['cargos.tipoCargo', 'pagos']);

        return redirect()->away($this->whatsapp->enlaceParaSocio($socio));
    }
}
