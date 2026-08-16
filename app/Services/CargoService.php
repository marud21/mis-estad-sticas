<?php

namespace App\Services;

use App\Models\Cargo;
use App\Models\Equipo;
use App\Models\Socio;
use App\Models\TipoCargo;
use Illuminate\Support\Facades\DB;

class CargoService
{
    /**
     * Crea un cargo. Si trae equipo_id pero no torneo_id, toma el torneo
     * del equipo en ese momento (foto del torneo actual: si el equipo
     * cambia de torneo despues, este cargo no se reasigna).
     */
    public function crear(Socio $socio, array $datos): Cargo
    {
        if (! empty($datos['equipo_id']) && empty($datos['torneo_id'])) {
            $datos['torneo_id'] = Equipo::find($datos['equipo_id'])?->torneo_id;
        }

        return $socio->cargos()->create($datos);
    }

    public function actualizar(Cargo $cargo, array $datos): Cargo
    {
        $cargo->update($datos);

        return $cargo;
    }

    public function eliminar(Cargo $cargo): void
    {
        $cargo->delete();
    }

    /**
     * Aplica un cargo recurrente (ej. mensualidad) a los socios activos y
     * suspendidos que aun no lo tengan registrado para la fecha indicada.
     * Los socios suspendidos pagan solo el porcentaje configurado en el tipo
     * de cargo (por defecto 25%) sobre el monto total.
     *
     * El alcance se puede limitar por nivel:
     * - "todos": un cargo general por socio, sin equipo asociado.
     * - "equipo"/"categoria": un cargo independiente POR CADA EQUIPO que
     *   coincida y por cada socio de ese equipo. Un socio que pertenece a
     *   varios equipos que califiquen (ej. dos equipos de la misma
     *   categoria) recibe un cargo por cada uno, de forma independiente.
     */
    public function aplicarMasivo(
        TipoCargo $tipoCargo,
        string $fecha,
        ?float $monto = null,
        string $nivel = 'todos',
        ?int $equipoId = null,
        ?string $categoria = null,
    ): int {
        $montoBase = $monto ?? (float) $tipoCargo->monto_default;
        $porcentajeSuspendido = (float) $tipoCargo->porcentaje_suspendido;
        $contador = 0;

        if ($nivel === 'todos') {
            DB::transaction(function () use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, &$contador) {
                Socio::whereIn('estado', [Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO])
                    ->whereDoesntHave('cargos', function ($query) use ($tipoCargo, $fecha) {
                        $query->where('tipo_cargo_id', $tipoCargo->id)->where('fecha', $fecha)->whereNull('equipo_id');
                    })
                    ->chunkById(100, function ($socios) use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, &$contador) {
                        foreach ($socios as $socio) {
                            $this->crearCargoIndividual($socio, $tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, null, null);
                            $contador++;
                        }
                    });
            });

            return $contador;
        }

        DB::transaction(function () use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, $nivel, $equipoId, $categoria, &$contador) {
            Equipo::when($nivel === 'equipo', fn ($q) => $q->where('id', $equipoId))
                ->when($nivel === 'categoria', fn ($q) => $q->where('categoria', $categoria))
                ->with(['socios' => fn ($q) => $q->whereIn('socios.estado', [Socio::ESTADO_ACTIVO, Socio::ESTADO_SUSPENDIDO])])
                ->get()
                ->each(function (Equipo $equipo) use ($tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, &$contador) {
                    foreach ($equipo->socios as $socio) {
                        $yaTiene = $socio->cargos()
                            ->where('tipo_cargo_id', $tipoCargo->id)
                            ->where('fecha', $fecha)
                            ->where('equipo_id', $equipo->id)
                            ->exists();

                        if ($yaTiene) {
                            continue;
                        }

                        $this->crearCargoIndividual($socio, $tipoCargo, $fecha, $montoBase, $porcentajeSuspendido, $equipo->id, $equipo->torneo_id);
                        $contador++;
                    }
                });
        });

        return $contador;
    }

    private function crearCargoIndividual(
        Socio $socio,
        TipoCargo $tipoCargo,
        string $fecha,
        float $montoBase,
        float $porcentajeSuspendido,
        ?int $equipoId,
        ?int $torneoId,
    ): void {
        $monto = $socio->estado === Socio::ESTADO_SUSPENDIDO
            ? round($montoBase * $porcentajeSuspendido / 100, 2)
            : $montoBase;

        $descripcion = $socio->estado === Socio::ESTADO_SUSPENDIDO
            ? "{$tipoCargo->nombre} ({$porcentajeSuspendido}% - suspendido)"
            : $tipoCargo->nombre;

        $socio->cargos()->create([
            'tipo_cargo_id' => $tipoCargo->id,
            'equipo_id' => $equipoId,
            'torneo_id' => $torneoId,
            'monto' => $monto,
            'fecha' => $fecha,
            'descripcion' => $descripcion,
        ]);
    }

    /**
     * Modifica el monto de cargos ya aplicados (mismo tipo de cargo y misma
     * fecha), por ejemplo cuando a un equipo se le monto la mensualidad
     * completa y luego se decide reducirsela. Respeta el porcentaje de
     * suspendido: los socios suspendidos quedan con el porcentaje del
     * nuevo monto, no del monto original. El nivel "equipo"/"categoria"
     * solo modifica los cargos que quedaron marcados con ese equipo (los
     * cargos generales, sin equipo, se modifican con el nivel "todos").
     */
    public function actualizarMasivo(
        TipoCargo $tipoCargo,
        string $fecha,
        float $nuevoMonto,
        string $nivel = 'todos',
        ?int $equipoId = null,
        ?string $categoria = null,
    ): int {
        $porcentajeSuspendido = (float) $tipoCargo->porcentaje_suspendido;
        $contador = 0;

        DB::transaction(function () use ($tipoCargo, $fecha, $nuevoMonto, $porcentajeSuspendido, $nivel, $equipoId, $categoria, &$contador) {
            Cargo::where('tipo_cargo_id', $tipoCargo->id)
                ->where('fecha', $fecha)
                ->when($nivel === 'todos', fn ($q) => $q->whereNull('equipo_id'))
                ->when($nivel === 'equipo', fn ($q) => $q->where('equipo_id', $equipoId))
                ->when($nivel === 'categoria', function ($q) use ($categoria) {
                    $q->whereHas('equipo', fn ($qq) => $qq->where('categoria', $categoria));
                })
                ->with('socio')
                ->chunkById(100, function ($cargos) use ($tipoCargo, $nuevoMonto, $porcentajeSuspendido, &$contador) {
                    foreach ($cargos as $cargo) {
                        $esSuspendido = $cargo->socio?->estado === Socio::ESTADO_SUSPENDIDO;

                        $monto = $esSuspendido
                            ? round($nuevoMonto * $porcentajeSuspendido / 100, 2)
                            : $nuevoMonto;

                        $descripcion = $esSuspendido
                            ? "{$tipoCargo->nombre} ({$porcentajeSuspendido}% - suspendido)"
                            : $tipoCargo->nombre;

                        $cargo->update(['monto' => $monto, 'descripcion' => $descripcion]);
                        $contador++;
                    }
                });
        });

        return $contador;
    }
}
