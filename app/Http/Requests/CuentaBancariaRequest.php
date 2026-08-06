<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CuentaBancariaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'banco' => ['required', 'string', 'max:255'],
            'tipo_cuenta' => ['required', 'string', 'max:100'],
            'numero_cuenta' => ['required', 'string', 'max:100'],
            'titular' => ['required', 'string', 'max:255'],
        ];
    }
}
