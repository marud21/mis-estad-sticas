<?php

namespace App\Services;

use App\Jobs\EnviarAvisoPush;
use App\Models\Cargo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\TipoCargo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RegistroPartidoService
{
    /**
     * Guarda lo que paso en el partido: goles y tarjetas por jugador.
     *
     * El marcador no se escribe a mano, se arma con los goles cargados
     * (un autogol suma al rival), para que las estadisticas y el resultado
     * nunca se contradigan. Cada tarjeta le genera al socio su cargo
     * correspondiente, con el mismo monto que el cobro de tarjetas.
     *
     * Registrar de nuevo reemplaza lo anterior: se borran las incidencias
     * previas y los cargos que habian generado, salvo los que ya tengan
     * pagos aplicados (esos se conservan para no perder ese historial).
     *
     * @param  array<int, array{socio_id:int, equipo_id:int, tipo:string}>  $incidencias
     * @param  int|null  $jugadorPartidoId  la figura del partido, si se eligio
     */
    public function registrar(Partido $partido, array $incidencias, ?int $jugadorPartidoId = null): Partido
    {
        $guardado = DB::transaction(function () use ($partido, $incidencias, $jugadorPartidoId) {
            $this->limpiarRegistroAnterior($partido);

            $tiposCargo = $this->tiposDeCargoDeTarjetas();

            foreach ($incidencias as $fila) {
                $socio = Socio::findOrFail($fila['socio_id']);

                $incidencia = $partido->incidencias()->create([
                    'socio_id' => $socio->id,
                    'equipo_id' => $fila['equipo_id'],
                    'tipo' => $fila['tipo'],
                ]);

                if ($incidencia->es_tarjeta) {
                    $this->cobrarTarjeta($incidencia, $socio, $partido, $tiposCargo);
                }
            }

            [$golesLocal, $golesVisitante] = $this->marcadorSegunGoles($partido->fresh('incidencias'), $partido);

            $partido->update([
                'goles_local' => $golesLocal,
                'goles_visitante' => $golesVisitante,
                'estado' => Partido::ESTADO_JUGADO,
                // Registrar de nuevo sin elegir figura la quita, igual que
                // pasa con las incidencias: manda lo ultimo que se guardo.
                'jugador_partido_id' => $jugadorPartidoId,
                // Si antes estaba marcado como walkover y ahora se carga
                // un resultado normal, deja de serlo.
                'equipo_ausente_id' => null,
            ]);

            return $partido;
        });

        $this->avisarDelResultado($guardado);

        return $guardado;
    }

    /**
     * Avisa a los navegadores suscritos que ya hay un resultado nuevo.
     *
     * Se encola despues de cerrar la transaccion, no dentro: si se hiciera
     * adentro, un aviso podria salir por un resultado que al final no se
     * guardo. Y si encolar falla, se deja anotado pero no se interrumpe:
     * el resultado ya esta guardado y eso es lo que importa.
     */
    private function avisarDelResultado(Partido $partido): void
    {
        $partido->loadMissing(['equipoLocal', 'equipoVisitante']);

        try {
            EnviarAvisoPush::dispatch(
                'Nuevo resultado',
                sprintf(
                    '%s %d - %d %s',
                    $partido->equipoLocal->nombre ?? 'Local',
                    $partido->goles_local,
                    $partido->goles_visitante,
                    $partido->equipoVisitante->nombre ?? 'Visitante',
                ),
                route('home', ['torneo_id' => $partido->torneo_id]),
            );
        } catch (Throwable $e) {
            Log::error('No se pudo encolar el aviso del resultado.', [
                'partido_id' => $partido->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Registra un partido ganado por walkover: uno de los equipos no se
     * presento y el marcador lo pone quien registra, porque no hay goles
     * que contar.
     *
     * Va aparte de registrar() y no como una variante suya porque es otra
     * operacion: aqui no hay incidencias, no hay figura y el marcador no
     * se deriva de nada. Si el partido traia un registro anterior, se
     * limpia igual que siempre (sin tocar cargos que ya tengan pagos):
     * si no se jugo, nadie pudo haber hecho un gol ni recibido una
     * tarjeta.
     */
    public function registrarWalkover(Partido $partido, int $equipoAusenteId, int $golesLocal, int $golesVisitante): Partido
    {
        $guardado = DB::transaction(function () use ($partido, $equipoAusenteId, $golesLocal, $golesVisitante) {
            $this->limpiarRegistroAnterior($partido);

            $partido->update([
                'goles_local' => $golesLocal,
                'goles_visitante' => $golesVisitante,
                'estado' => Partido::ESTADO_JUGADO,
                'equipo_ausente_id' => $equipoAusenteId,
                // Nadie jugo, asi que no puede haber figura del partido.
                'jugador_partido_id' => null,
            ]);

            return $partido;
        });

        $this->avisarDelResultado($guardado);

        return $guardado;
    }

    /**
     * Socios que ya tienen una tarjeta cobrada el mismo dia del partido por
     * fuera de este registro (por ejemplo desde "Cobro de tarjetas"). Sirve
     * para avisar antes de cobrarles dos veces lo mismo.
     *
     * @param  array<int, array{socio_id:int, equipo_id:int, tipo:string}>  $incidencias
     * @return Collection<int, string> nombres de los socios en riesgo
     */
    public function sociosConTarjetaCobradaEseDia(Partido $partido, array $incidencias): Collection
    {
        $idsConTarjeta = collect($incidencias)
            ->filter(fn ($fila) => array_key_exists($fila['tipo'], IncidenciaPartido::TARJETAS))
            ->pluck('socio_id')
            ->unique();

        if ($idsConTarjeta->isEmpty()) {
            return collect();
        }

        $tiposTarjeta = $this->tiposDeCargoDeTarjetas()->pluck('id');

        return Cargo::whereIn('socio_id', $idsConTarjeta)
            ->whereIn('tipo_cargo_id', $tiposTarjeta)
            ->whereDate('fecha', $partido->fecha_hora->toDateString())
            // Los cargos de este mismo partido no cuentan: se van a reemplazar.
            ->whereNotIn('id', $partido->incidencias()->whereNotNull('cargo_id')->pluck('cargo_id'))
            ->with('socio')
            ->get()
            ->pluck('socio.nombre_completo')
            ->unique()
            ->values();
    }

    private function limpiarRegistroAnterior(Partido $partido): void
    {
        $incidencias = $partido->incidencias()->with('cargo')->get();

        foreach ($incidencias as $incidencia) {
            $cargo = $incidencia->cargo;

            // Nunca se borra un cargo que ya tenga pagos aplicados.
            if ($cargo && $cargo->pagos()->count() === 0) {
                $incidencia->update(['cargo_id' => null]);
                $cargo->delete();
            }

            $incidencia->delete();
        }
    }

    private function cobrarTarjeta(IncidenciaPartido $incidencia, Socio $socio, Partido $partido, Collection $tiposCargo): void
    {
        $tipoCargo = $tiposCargo->get(IncidenciaPartido::TARJETAS[$incidencia->tipo]);

        // Si el tipo de cargo no existe no se interrumpe el registro del
        // partido: la incidencia queda guardada, solo sin cargo asociado.
        if (! $tipoCargo) {
            return;
        }

        $cargo = $socio->cargos()->create([
            'tipo_cargo_id' => $tipoCargo->id,
            'equipo_id' => $incidencia->equipo_id,
            'torneo_id' => $partido->torneo_id,
            'monto' => $tipoCargo->monto_default,
            'fecha' => $partido->fecha_hora->toDateString(),
            'descripcion' => $tipoCargo->nombre.' - '.$this->nombreDelPartido($partido),
        ]);

        $incidencia->update(['cargo_id' => $cargo->id]);
    }

    /**
     * Arma el marcador con los goles registrados: el autogol de un jugador
     * suma para el equipo contrario.
     *
     * @return array{0:int, 1:int}
     */
    private function marcadorSegunGoles(Partido $conIncidencias, Partido $partido): array
    {
        $golesLocal = 0;
        $golesVisitante = 0;

        foreach ($conIncidencias->incidencias as $incidencia) {
            $esDelLocal = $incidencia->equipo_id === $partido->equipo_local_id;

            if ($incidencia->tipo === IncidenciaPartido::TIPO_GOL) {
                $esDelLocal ? $golesLocal++ : $golesVisitante++;
            } elseif ($incidencia->tipo === IncidenciaPartido::TIPO_AUTOGOL) {
                $esDelLocal ? $golesVisitante++ : $golesLocal++;
            }
        }

        return [$golesLocal, $golesVisitante];
    }

    /** @return Collection<string, TipoCargo> indexada por nombre */
    private function tiposDeCargoDeTarjetas(): Collection
    {
        return TipoCargo::whereIn('nombre', array_values(IncidenciaPartido::TARJETAS))
            ->get()
            ->keyBy('nombre');
    }

    private function nombreDelPartido(Partido $partido): string
    {
        return sprintf(
            '%s vs %s (%s)',
            $partido->equipoLocal->nombre ?? 'Local',
            $partido->equipoVisitante->nombre ?? 'Visitante',
            $partido->fecha_hora->format('d/m/Y'),
        );
    }
}
