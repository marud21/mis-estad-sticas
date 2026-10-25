<?php

namespace App\Http\Requests;

use App\Models\IncidenciaPartido;
use Illuminate\Foundation\Http\FormRequest;

class RegistroPartidoRequest extends FormRequest
{
    /** Cuantas veces como maximo puede repetirse lo mismo en un jugador. */
    private const TOPE_POR_JUGADOR = 20;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * El formulario llega como jugadores[socio_id][tipo] = cantidad, con el
     * equipo al que pertenece cada jugador.
     */
    public function rules(): array
    {
        $tipos = implode(',', array_keys(IncidenciaPartido::TIPOS));

        return [
            'jugadores' => ['sometimes', 'array'],
            'jugadores.*.equipo_id' => ['required', 'exists:equipos,id'],
            'jugadores.*.cantidades' => ['sometimes', 'array'],
            'jugadores.*.cantidades.*' => ['nullable', 'integer', 'min:0', 'max:'.self::TOPE_POR_JUGADOR],
            'confirmado' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'jugadores.*.cantidades.*.max' => 'Un jugador no puede tener mas de '.self::TOPE_POR_JUGADOR.' veces la misma incidencia.',
            'jugadores.*.cantidades.*.min' => 'Las cantidades no pueden ser negativas.',
        ];
    }

    /**
     * Convierte las cantidades del formulario en una incidencia por cada
     * gol o tarjeta: 2 amarillas de un jugador son dos incidencias, y por
     * lo tanto dos cargos.
     *
     * @return array<int, array{socio_id:int, equipo_id:int, tipo:string}>
     */
    public function incidencias(): array
    {
        $incidencias = [];

        foreach ($this->input('jugadores', []) as $socioId => $datos) {
            $equipoId = (int) ($datos['equipo_id'] ?? 0);

            foreach ($datos['cantidades'] ?? [] as $tipo => $cantidad) {
                if (! array_key_exists($tipo, IncidenciaPartido::TIPOS)) {
                    continue;
                }

                $cantidad = (int) $cantidad;

                for ($i = 0; $i < $cantidad; $i++) {
                    $incidencias[] = [
                        'socio_id' => (int) $socioId,
                        'equipo_id' => $equipoId,
                        'tipo' => $tipo,
                    ];
                }
            }
        }

        return $incidencias;
    }
}
