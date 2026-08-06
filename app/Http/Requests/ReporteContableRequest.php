<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReporteContableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['dia', 'mes', 'rango'])],
            'fecha' => ['required_if:tipo,dia', 'nullable', 'date'],
            'mes' => ['required_if:tipo,mes', 'nullable', 'date_format:Y-m'],
            'fecha_inicio' => ['required_if:tipo,rango', 'nullable', 'date'],
            'fecha_fin' => ['required_if:tipo,rango', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }
}
