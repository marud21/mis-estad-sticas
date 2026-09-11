<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TarjetasEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tarjetas' => ['required', 'array', 'min:1'],
            'tarjetas.*.socio_id' => ['required', 'exists:socios,id'],
            'tarjetas.*.tipo_cargo_id' => ['required', 'exists:tipos_cargo,id'],
            'tarjetas.*.fecha' => ['required', 'date'],
        ];
    }
}
