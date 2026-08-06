<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TipoCargoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'monto_default' => ['required', 'numeric', 'min:0'],
            'es_recurrente' => ['boolean'],
            'porcentaje_suspendido' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['es_recurrente' => $this->boolean('es_recurrente')]);
    }
}
