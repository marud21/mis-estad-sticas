<?php

namespace App\Http\Requests;

use App\Models\IncidenciaPartido;
use App\Models\Socio;
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
            // La figura es opcional, pero si se elige tiene que ser de uno
            // de los dos equipos que jugaron: no vale cualquier socio.
            'jugador_partido_id' => ['nullable', 'integer', $this->deAlgunoDeLosDosEquipos()],
            'confirmado' => ['sometimes', 'boolean'],

            // Walkover: si se marca, hay que decir quien falto y con que
            // marcador queda, porque no hay goles de donde deducirlo.
            'walkover' => ['sometimes', 'boolean'],
            'equipo_ausente_id' => ['required_if:walkover,1', 'nullable', 'integer', $this->unoDeLosDosEquipos()],
            'goles_local' => ['required_if:walkover,1', 'nullable', 'integer', 'min:0', 'max:99'],
            'goles_visitante' => ['required_if:walkover,1', 'nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    public function esWalkover(): bool
    {
        return $this->boolean('walkover');
    }

    /** El ausente tiene que ser el local o el visitante de este partido. */
    private function unoDeLosDosEquipos(): callable
    {
        return function (string $atributo, mixed $valor, callable $fallar) {
            $partido = $this->route('partido');

            if (! $partido || blank($valor)) {
                return;
            }

            if (! in_array((int) $valor, [$partido->equipo_local_id, $partido->equipo_visitante_id], true)) {
                $fallar('El equipo ausente debe ser uno de los dos que iban a jugar.');
            }
        };
    }

    private function deAlgunoDeLosDosEquipos(): callable
    {
        return function (string $atributo, mixed $valor, callable $fallar) {
            $partido = $this->route('partido');

            if (! $partido) {
                return;
            }

            $juega = Socio::whereKey($valor)
                ->whereHas('equipos', fn ($q) => $q->whereIn(
                    'equipos.id',
                    [$partido->equipo_local_id, $partido->equipo_visitante_id],
                ))
                ->exists();

            if (! $juega) {
                $fallar('El jugador del partido debe pertenecer a uno de los dos equipos.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'jugadores.*.cantidades.*.max' => 'Un jugador no puede tener mas de '.self::TOPE_POR_JUGADOR.' veces la misma incidencia.',
            'jugadores.*.cantidades.*.min' => 'Las cantidades no pueden ser negativas.',
            'equipo_ausente_id.required_if' => 'Indica cual de los dos equipos no se presento.',
            'goles_local.required_if' => 'Escribe el marcador con el que queda el partido.',
            'goles_visitante.required_if' => 'Escribe el marcador con el que queda el partido.',
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
