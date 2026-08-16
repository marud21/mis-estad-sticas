<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $socio = $this->route('socio');

        return [
            'tipo_cargo_id' => ['required', 'exists:tipos_cargo,id'],
            'monto' => ['required', 'numeric', 'min:0'],
            'fecha' => ['required', 'date'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'equipo_id' => ['nullable', Rule::in($socio?->equipos->pluck('id')->all() ?? [])],
        ];
    }
}
