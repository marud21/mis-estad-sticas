<?php

namespace App\Support;

use App\Models\Equipo;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * Los parametros con los que se mira el panel.
 *
 * Van juntos en un objeto y no sueltos como cuatro argumentos, porque se
 * pasan a casi todas las consultas y asi se agrega uno nuevo sin tocar
 * ninguna firma.
 *
 * El rango de fechas solo afecta a lo que ocurre en el tiempo (cargos,
 * pagos, partidos). Los conteos de socios y equipos son una foto de hoy:
 * cuantos socios activos hay "entre marzo y junio" no significa nada.
 */
class FiltroPanel
{
    public function __construct(
        public readonly CarbonImmutable $desde,
        public readonly CarbonImmutable $hasta,
        public readonly ?int $torneoId = null,
        public readonly ?string $categoria = null,
        public readonly ?int $equipoId = null,
    ) {
    }

    public static function desdeLaPeticion(Request $request): self
    {
        $hoy = CarbonImmutable::today();

        $desde = self::fecha($request->input('desde')) ?? $hoy->startOfYear();
        $hasta = self::fecha($request->input('hasta')) ?? $hoy->endOfYear();

        // Si llegan al reves se enderezan, en vez de devolver cero datos
        // y dejar al usuario pensando que no hay informacion.
        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self(
            desde: $desde,
            hasta: $hasta,
            torneoId: $request->integer('torneo_id') ?: null,
            categoria: $request->filled('categoria') ? (string) $request->input('categoria') : null,
            equipoId: $request->integer('equipo_id') ?: null,
        );
    }

    private static function fecha(mixed $valor): ?CarbonImmutable
    {
        if (blank($valor)) {
            return null;
        }

        try {
            return CarbonImmutable::parse($valor)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    public function hayFiltroDeEquipos(): bool
    {
        return $this->torneoId !== null || $this->categoria !== null || $this->equipoId !== null;
    }

    /**
     * Los equipos que caen dentro del filtro. Null significa "todos", que
     * no es lo mismo que una lista vacia (un filtro que no coincide con
     * ningun equipo y por lo tanto no debe mostrar nada).
     *
     * @return array<int, int>|null
     */
    public function equiposAlcanzados(): ?array
    {
        if (! $this->hayFiltroDeEquipos()) {
            return null;
        }

        return Equipo::query()
            ->when($this->torneoId, fn ($q) => $q->where('torneo_id', $this->torneoId))
            ->when($this->categoria, fn ($q) => $q->where('categoria', $this->categoria))
            ->when($this->equipoId, fn ($q) => $q->whereKey($this->equipoId))
            ->pluck('id')
            ->all();
    }

    /** Los parametros tal como viajan en la URL, para los enlaces. */
    public function comoParametros(): array
    {
        return array_filter([
            'desde' => $this->desde->toDateString(),
            'hasta' => $this->hasta->toDateString(),
            'torneo_id' => $this->torneoId,
            'categoria' => $this->categoria,
            'equipo_id' => $this->equipoId,
        ], fn ($v) => $v !== null);
    }

    public function etiquetaRango(): string
    {
        return $this->desde->format('d/m/Y').' al '.$this->hasta->format('d/m/Y');
    }
}
