<?php

namespace App\Http\Requests;

use App\Models\Pago;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagosMultiplesEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pagos' => ['required', 'array', 'min:1'],
            'pagos.*.socio_id' => ['required', 'exists:socios,id'],
            'pagos.*.valor' => ['required', 'numeric', 'min:0.01'],
            'pagos.*.tipo' => ['required', Rule::in([Pago::TIPO_EFECTIVO, Pago::TIPO_TRANSFERENCIA])],
        ];
    }
}
