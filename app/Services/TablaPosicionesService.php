<?php

namespace App\Services;

use App\Models\AjustePuntos;
use App\Models\Equipo;
use App\Models\Partido;
use App\Models\Torneo;
use Illuminate\Support\Collection;

class TablaPosicionesService
{
    /** Puntos por resultado. */
    private const PUNTOS_VICTORIA = 3;

    private const PUNTOS_EMPATE = 1;

    /**
     * Arma la tabla de posiciones de un torneo con los partidos ya jugados.
     *
     * Solo se listan los equipos activos. Los partidos de un equipo que ya
     * se desactivo si se siguen contando: el rival los jugo de verdad y
     * descontarlos le cambiaria los puntos a quien no tiene la culpa.
     */
    public function calcular(Torneo $torneo): Collection
    {
        $partidos = Partido::where('torneo_id', $torneo->id)
            ->computables()
            ->get(['equipo_local_id', 'equipo_visitante_id', 'goles_local', 'goles_visitante']);

        // Puntos sumados o restados por fuera de los partidos (sanciones,
        // bonificaciones), agrupados por equipo en una sola consulta.
        $ajustes = AjustePuntos::where('torneo_id', $torneo->id)
            ->get()
            ->groupBy('equipo_id')
            ->map(fn (Collection $delEquipo) => $delEquipo->sum('puntos'));

        $tabla = Equipo::where('torneo_id', $torneo->id)
            ->where('estado', Equipo::ESTADO_ACTIVO)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Equipo $equipo) => $this->estadisticasDelEquipo(
                $equipo,
                $partidos,
                (int) ($ajustes[$equipo->id] ?? 0),
            ));

        return $this->ordenarConDesempates($tabla);
    }

    /**
     * Suma o resta puntos a un equipo por fuera de los partidos (sanciones
     * del comite, bonificaciones). Vive aqui porque este servicio es el que
     * sabe como se componen los puntos de la tabla.
     */
    public function registrarAjuste(array $datos, ?int $userId = null): AjustePuntos
    {
        return AjustePuntos::create($datos + ['user_id' => $userId]);
    }

    public function eliminarAjuste(AjustePuntos $ajuste): void
    {
        $ajuste->delete();
    }

    private function estadisticasDelEquipo(Equipo $equipo, Collection $partidos, int $ajuste = 0): array
    {
        $fila = [
            'posicion' => 0,
            'equipo_id' => $equipo->id,
            'equipo' => $equipo->nombre,
            'pj' => 0,
            'pg' => 0,
            'pe' => 0,
            'pp' => 0,
            'gf' => 0,
            'gc' => 0,
            'dg' => 0,
            'pts' => 0,
            'ajuste' => $ajuste,
        ];

        $suyos = $partidos->filter(
            fn (Partido $p) => $p->equipo_local_id === $equipo->id || $p->equipo_visitante_id === $equipo->id
        );

        foreach ($suyos as $partido) {
            [$golesAFavor, $golesEnContra] = $this->golesDesdeLaPerspectivaDe($partido, $equipo->id);

            $fila['pj']++;
            $fila['gf'] += $golesAFavor;
            $fila['gc'] += $golesEnContra;

            if ($golesAFavor > $golesEnContra) {
                $fila['pg']++;
                $fila['pts'] += self::PUNTOS_VICTORIA;
            } elseif ($golesAFavor === $golesEnContra) {
                $fila['pe']++;
                $fila['pts'] += self::PUNTOS_EMPATE;
            } else {
                $fila['pp']++;
            }
        }

        $fila['dg'] = $fila['gf'] - $fila['gc'];

        // El ajuste entra en los puntos finales, asi que tambien pesa en el
        // orden de la tabla y no solo como un dato informativo.
        $fila['pts'] += $ajuste;

        return $fila;
    }

    private function golesDesdeLaPerspectivaDe(Partido $partido, int $equipoId): array
    {
        return $partido->equipo_local_id === $equipoId
            ? [$partido->goles_local, $partido->goles_visitante]
            : [$partido->goles_visitante, $partido->goles_local];
    }

    /**
     * Orden de la tabla:
     *   1. Puntos                 (mas es mejor)
     *   2. Diferencia de gol      (mas es mejor)
     *   3. Goles a favor          (mas es mejor)
     *   4. Nombre del equipo      (para que el orden sea estable)
     *
     * Cuando se integre el modulo de estadisticas se agregan aqui los
     * criterios por tarjetas rojas y amarillas (menos es mejor), que es
     * como se desempata en el reglamento.
     */
    private function ordenarConDesempates(Collection $tabla): Collection
    {
        return $tabla
            ->sort(fn (array $a, array $b) => $b['pts'] <=> $a['pts']
                ?: $b['dg'] <=> $a['dg']
                ?: $b['gf'] <=> $a['gf']
                ?: strcasecmp($a['equipo'], $b['equipo']))
            ->values()
            ->map(function (array $fila, int $indice) {
                $fila['posicion'] = $indice + 1;

                return $fila;
            });
    }
}
