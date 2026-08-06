<?php

namespace App\Http\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'numeric', 'min:0.01'],
            'fecha' => ['required', 'date'],
            'tipo' => ['required', Rule::in([Pago::TIPO_EFECTIVO, Pago::TIPO_TRANSFERENCIA])],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'cargo_id' => ['nullable', 'exists:cargos,id'],
        ];
    }
}
