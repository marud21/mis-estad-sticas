<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Socio;
use App\Services\ReporteService;
use App\Services\WhatsAppService;

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
