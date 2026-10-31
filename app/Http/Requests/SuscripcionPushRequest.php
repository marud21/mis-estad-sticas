<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SuscripcionPushRequest extends FormRequest
{
    /** Es publico a proposito: cualquiera puede pedir que le avisen. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'max:1000', 'url'],
            // Se comprueba el tamaño real de las claves, no solo que vengan.
            // Una clave mal formada hace fallar el cifrado al momento de
            // enviar, y como el envio es en lote, una sola echaria a perder
            // el aviso de todos los demas. Mejor no dejarla entrar.
            'clave_p256dh' => ['required', 'string', 'max:255', $this->bytes(65)],
            'clave_auth' => ['required', 'string', 'max:255', $this->bytes(16)],
        ];
    }

    public function messages(): array
    {
        return [
            'clave_p256dh.*' => 'La clave del navegador no tiene el formato esperado.',
            'clave_auth.*' => 'La clave del navegador no tiene el formato esperado.',
        ];
    }

    /**
     * Regla: el texto debe ser base64 "de url" y pesar exactamente los
     * bytes que manda la especificacion de Web Push.
     */
    private function bytes(int $esperados): callable
    {
        return function (string $atributo, mixed $valor, callable $fallar) use ($esperados) {
            $crudo = base64_decode(strtr($valor, '-_', '+/'), true);

            if ($crudo === false || strlen($crudo) !== $esperados) {
                $fallar('La clave del navegador no tiene el formato esperado.');
            }
        };
    }
}
