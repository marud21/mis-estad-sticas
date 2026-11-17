<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Asignarle una contrasena nueva a un directivo.
 *
 * El administrador no necesita saber la contrasena anterior: la idea es
 * justamente poder reponerla cuando el directivo la perdio. Lo que si se
 * le pide es la suya, que es la regla "current_password": comprueba la
 * contrasena del usuario con la sesion abierta, o sea el administrador.
 *
 * Sin eso, cualquiera que encuentre un computador con la sesion del
 * administrador abierta se queda con la cuenta del directivo sin haber
 * sabido nunca una sola clave.
 */
class PasswordDirectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_admin' => ['required', 'string', 'current_password'],
        ];
    }

    public function attributes(): array
    {
        return [
            'password' => 'contraseña nueva',
            'password_admin' => 'su contraseña de administrador',
        ];
    }

    public function messages(): array
    {
        return [
            'password.confirmed' => 'Las dos contraseñas nuevas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password_admin.required' => 'Escriba su propia contraseña de administrador para confirmar.',
            'password_admin.current_password' => 'Su contraseña de administrador no es correcta.',
        ];
    }
}
