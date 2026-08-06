<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlanillaJuegoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipo_local_id' => ['required', 'exists:equipos,id', 'different:equipo_visitante_id'],
            'equipo_visitante_id' => ['required', 'exists:equipos,id'],
            'torneo' => ['nullable', 'string', 'max:255'],
            'jornada' => ['nullable', 'string', 'max:100'],
            'cancha' => ['nullable', 'string', 'max:100'],
            'fecha' => ['nullable', 'date'],
            'hora' => ['nullable', 'string', 'max:20'],
            'arbitro' => ['nullable', 'string', 'max:255'],
        ];
    }
}
