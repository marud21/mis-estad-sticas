<?php

namespace App\Http\Requests;

use App\Models\Partido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PartidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'torneo_id' => ['required', 'exists:torneos,id'],
            'equipo_local_id' => ['required', 'exists:equipos,id', 'different:equipo_visitante_id'],
            'equipo_visitante_id' => ['required', 'exists:equipos,id'],
            'fecha_hora' => ['required', 'date'],
            'lugar' => ['nullable', 'string', 'max:150'],
            'fase' => ['nullable', 'string', 'max:50'],
            'arbitro' => ['nullable', 'string', 'max:150'],
            'estado' => ['required', Rule::in(array_keys(Partido::ESTADOS))],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            // El marcador solo se exige cuando el partido se marca como jugado.
            'goles_local' => ['nullable', 'integer', 'min:0', 'max:255', 'required_if:estado,'.Partido::ESTADO_JUGADO],
            'goles_visitante' => ['nullable', 'integer', 'min:0', 'max:255', 'required_if:estado,'.Partido::ESTADO_JUGADO],
        ];
    }

    public function messages(): array
    {
        return [
            'equipo_local_id.different' => 'Un equipo no puede jugar contra si mismo.',
            'goles_local.required_if' => 'Para marcar el partido como jugado hay que cargar el marcador.',
            'goles_visitante.required_if' => 'Para marcar el partido como jugado hay que cargar el marcador.',
        ];
    }
}
