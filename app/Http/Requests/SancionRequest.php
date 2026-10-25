<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SancionRequest extends FormRequest
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
            'socio_id' => ['required', 'exists:socios,id'],
            'fechas' => ['required', 'integer', 'min:1', 'max:100'],
            'motivo' => ['required', 'string', 'max:255'],
            'fecha_desde' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'fechas.min' => 'La sancion debe ser de al menos una fecha.',
            'motivo.required' => 'Indica el motivo de la sancion para que quede justificada.',
            'socio_id.required' => 'Elige al jugador sancionado.',
        ];
    }
}
