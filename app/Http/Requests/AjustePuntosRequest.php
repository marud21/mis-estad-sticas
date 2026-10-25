<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjustePuntosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'torneo_id' => ['required', 'exists:torneos,id'],
            'equipo_id' => ['required', 'exists:equipos,id'],
            // Con signo: negativo descuenta, positivo bonifica. No tiene
            // sentido un ajuste de cero.
            'puntos' => ['required', 'integer', 'between:-50,50', 'not_in:0'],
            'motivo' => ['required', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'puntos.not_in' => 'El ajuste no puede ser de cero puntos.',
            'puntos.between' => 'El ajuste debe estar entre -50 y 50 puntos.',
            'motivo.required' => 'Indica el motivo del ajuste para que quede justificado en la tabla.',
        ];
    }
}
