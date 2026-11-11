<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Intentos fallidos seguidos antes de bloquear. */
    private const INTENTOS_MAXIMOS = 5;

    /** Cuanto dura el bloqueo, en segundos. */
    private const BLOQUEO = 60;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Corta el paso si ya hubo demasiados intentos fallidos.
     *
     * Se cuenta por correo y direccion juntos, no solo por direccion: si
     * se contara solo por correo, cualquiera podria dejar fuera al
     * administrador fallando a proposito desde otro lado.
     */
    public function asegurarQueNoEstaBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->llaveDeIntentos(), self::INTENTOS_MAXIMOS)) {
            return;
        }

        $segundos = RateLimiter::availableIn($this->llaveDeIntentos());

        throw ValidationException::withMessages([
            'email' => $segundos >= 60
                ? 'Demasiados intentos fallidos. Vuelve a intentarlo en un minuto.'
                : "Demasiados intentos fallidos. Vuelve a intentarlo en {$segundos} segundos.",
        ]);
    }

    public function registrarIntentoFallido(): void
    {
        RateLimiter::hit($this->llaveDeIntentos(), self::BLOQUEO);
    }

    /** Al entrar bien se borra la cuenta, para no castigar al que acerto. */
    public function limpiarIntentos(): void
    {
        RateLimiter::clear($this->llaveDeIntentos());
    }

    private function llaveDeIntentos(): string
    {
        return 'login|'.Str::transliterate(Str::lower((string) $this->input('email')).'|'.$this->ip());
    }
}
