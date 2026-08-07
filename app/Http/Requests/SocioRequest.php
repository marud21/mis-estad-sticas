<?php

namespace App\Http\Requests;

use App\Models\Socio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $socioId = $this->route('socio')?->id;

        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'numero_documento' => ['required', 'string', 'max:50', Rule::unique('socios', 'numero_documento')->ignore($socioId)],
            'fecha_nacimiento' => ['nullable', 'date'],
            'fecha_ingreso' => ['nullable', 'date'],
            'entidad_salud' => ['nullable', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:20'],
            'tipo_sangre' => ['nullable', 'string', 'max:10'],
            'direccion_residencia' => ['nullable', 'string', 'max:255'],
            'posicion_juego' => ['nullable', 'string', 'max:100'],
            'numero_camiseta' => ['nullable', 'integer', 'min:0', 'max:999'],
            'foto' => ['nullable', 'image', 'max:15360'],
            'nivel_jugador' => ['nullable', 'integer', 'in:1,2,3'],
            'estado' => ['sometimes', Rule::in([Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO, Socio::ESTADO_RETIRADO])],
            'equipo_id' => ['nullable', 'exists:equipos,id'],
            'cargos' => ['sometimes', 'array'],
            'cargos.*.tipo_cargo_id' => ['nullable', 'exists:tipos_cargo,id'],
            'cargos.*.monto' => ['nullable', 'required_with:cargos.*.tipo_cargo_id', 'numeric', 'min:0'],
            'cargos.*.fecha' => ['nullable', 'required_with:cargos.*.tipo_cargo_id', 'date'],
            'cargos.*.descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
