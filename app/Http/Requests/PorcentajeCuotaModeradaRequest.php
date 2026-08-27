<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PorcentajeCuotaModeradaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'porcentaje' => ['required', 'numeric', 'min:1', 'max:100'],
        ];
    }
}
