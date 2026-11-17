<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Datos de una cuenta de directivo: al crearla y al editarla.
 *
 * El correo tiene que ser unico entre TODOS los usuarios, no solo entre
 * los directivos: es con lo que se entra al sistema y la tabla lo tiene
 * declarado unico. Si se repitiera el del administrador, la base lo
 * rechazaria con un error que no le dice nada a nadie.
 */
class DirectivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $directivo = $this->route('directivo');
        $esNuevo = $directivo === null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($directivo),
            ],

            // Al crear la cuenta hace falta una clave. Al editar nombre y
            // correo se prohibe: la clave tiene su propio formulario, que
            // pide la del administrador para confirmar. Sin esta regla,
            // este formulario seria un camino para cambiarla sin esa
            // confirmacion.
            'password' => $esNuevo
                ? ['required', 'string', 'min:8', 'confirmed']
                : ['prohibited'],

            // El rol no se recibe de afuera nunca: lo fija el servicio.
            // Se declara prohibido para que un campo colado en la
            // peticion falle a la vista y no pase en silencio.
            'role' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya hay un usuario con ese correo.',
            'password.confirmed' => 'Las dos contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.prohibited' => 'La contraseña se cambia en su propio formulario.',
            'role.prohibited' => 'El rol no se puede cambiar desde aqui.',
        ];
    }
}
