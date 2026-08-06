<?php

namespace App\Services;

use App\Models\Socio;

class WhatsAppService
{
    private const CODIGO_PAIS = '57';

    public function enlaceParaSocio(Socio $socio): string
    {
        $telefono = $this->normalizarTelefono($socio->celular);
        $mensaje = $this->construirMensaje($socio);

        return "https://wa.me/{$telefono}?text=" . rawurlencode($mensaje);
    }

    private function normalizarTelefono(string $celular): string
    {
        $digitos = preg_replace('/\D/', '', $celular);

        if (! str_starts_with($digitos, self::CODIGO_PAIS)) {
            $digitos = self::CODIGO_PAIS . $digitos;
        }

        return $digitos;
    }

    private function construirMensaje(Socio $socio): string
    {
        $lineas = [];
        $lineas[] = "Hola {$socio->nombre_completo}, este es tu estado de cuenta en Corvepatios:";
        $lineas[] = '';
        $lineas[] = 'CARGOS:';

        foreach ($socio->cargos as $cargo) {
            $lineas[] = sprintf(
                '- %s: $%s (%s)',
                $cargo->tipoCargo->nombre,
                number_format((float) $cargo->monto, 0, ',', '.'),
                $cargo->fecha->format('d/m/Y')
            );
        }

        if ($socio->cargos->isEmpty()) {
            $lineas[] = '- Sin cargos registrados';
        }

        $lineas[] = '';
        $lineas[] = 'PAGOS:';

        foreach ($socio->pagos as $pago) {
            $lineas[] = sprintf(
                '- $%s el %s (%s)',
                number_format((float) $pago->valor, 0, ',', '.'),
                $pago->fecha->format('d/m/Y'),
                ucfirst($pago->tipo)
            );
        }

        if ($socio->pagos->isEmpty()) {
            $lineas[] = '- Sin pagos registrados';
        }

        $lineas[] = '';
        $lineas[] = 'Deuda total: $' . number_format($socio->deuda_total, 0, ',', '.');

        return implode("\n", $lineas);
    }
}
