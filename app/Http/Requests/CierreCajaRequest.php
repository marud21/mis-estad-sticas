<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CierreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date', Rule::unique('cierres_caja', 'fecha')],
            'notas' => ['nullable', 'string', 'max:1000'],
            'gastos' => ['sometimes', 'array'],
            'gastos.*.descripcion' => ['required_with:gastos.*.monto', 'nullable', 'string', 'max:255'],
            'gastos.*.monto' => ['required_with:gastos.*.descripcion', 'nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.unique' => 'Ya existe un cierre de caja guardado para esta fecha.',
        ];
    }
}
