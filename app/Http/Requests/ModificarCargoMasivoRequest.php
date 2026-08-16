<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModificarCargoMasivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
            'nivel' => ['required', Rule::in(['todos', 'equipo', 'categoria'])],
            'equipo_id' => ['required_if:nivel,equipo', 'nullable', 'exists:equipos,id'],
            'categoria' => ['required_if:nivel,categoria', 'nullable', 'string'],
        ];
    }
}
