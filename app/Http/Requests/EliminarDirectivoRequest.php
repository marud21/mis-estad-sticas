<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Borrar una cuenta de directivo.
 *
 * Se pide la contrasena del administrador por lo mismo que al cambiar la
 * clave: es una accion que no se deshace y no debe poder hacerla alguien
 * que solo se encontro la sesion abierta.
 */
class EliminarDirectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_admin' => ['required', 'string', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_admin.required' => 'Escriba su propia contraseña de administrador para confirmar.',
            'password_admin.current_password' => 'Su contraseña de administrador no es correcta.',
        ];
    }
}
