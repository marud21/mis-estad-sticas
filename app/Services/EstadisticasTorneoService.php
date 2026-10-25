<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\IncidenciaPartido;
use App\Models\Partido;
use App\Models\Sancion;
use App\Models\Torneo;
use Illuminate\Support\Collection;

/**
 * Estadisticas de un torneo para la pagina publica: goleadores,
 * sancionados, valla menos vencida y resultados.
 *
 * Todo se arma con pocas consultas agrupadas en vez de recorrer jugador
 * por jugador, porque esta pantalla la puede abrir cualquiera.
 */
class EstadisticasTorneoService
{
    public function __construct(private readonly TablaPosicionesService $posiciones)
    {
    }

    /**
     * Posiciones separadas por categoria de equipo. Los equipos sin
     * categoria se agrupan aparte para que no desaparezcan de la vista.
     *
     * @return Collection<string, Collection>
     */
    public function posicionesPorCategoria(Torneo $torneo): Collection
    {
        $categorias = $this->categoriasDeEquipos($torneo);

        return $this->posiciones->calcular($torneo)
            ->groupBy(fn (array $fila) => $categorias[$fila['equipo_id']] ?? $this->nombreCategoria(null))
            ->map(fn (Collection $filas) => $filas->values()->map(function (array $fila, int $i) {
                // La posicion se renumera dentro de su categoria.
                $fila['posicion'] = $i + 1;

                return $fila;
            }))
            ->sortKeys();
    }

    /**
     * Goleadores separados por categoria. El autogol no le suma a quien lo
     * hizo. Cada gol se cuenta en la categoria del equipo por el que se
     * anoto, asi que un partido entre categorias distintas le suma a cada
     * una lo suyo.
     *
     * @return Collection<string, Collection<int, object>>
     */
    public function goleadoresPorCategoria(Torneo $torneo): Collection
    {
        return $this->incidenciasDelTorneo($torneo, [IncidenciaPartido::TIPO_GOL])
            ->groupBy(fn (IncidenciaPartido $gol) => $this->nombreCategoria($gol->equipo?->categoria))
            ->map(fn (Collection $goles) => $goles
                ->groupBy('socio_id')
                ->map(fn (Collection $suyos) => (object) [
                    'jugador' => $suyos->first()->socio->nombre_completo ?? 'Socio eliminado',
                    'equipo' => $suyos->first()->equipo->nombre ?? '-',
                    'goles' => $suyos->count(),
                ])
                ->sortBy([
                    fn ($a, $b) => $b->goles <=> $a->goles,
                    fn ($a, $b) => strcasecmp($a->jugador, $b->jugador),
                ])
                ->values())
            ->sortKeys();
    }

    /**
     * Jugadores sancionados por la corporacion: los que tienen fechas por
     * cumplir. Es distinto de las tarjetas, que son lo que paso en cancha.
     *
     * @return Collection<int, object>
     */
    public function sancionados(Torneo $torneo): Collection
    {
        return Sancion::with(['socio:id,nombre_completo', 'equipo:id,nombre'])
            ->where('torneo_id', $torneo->id)
            ->vigentes()
            ->get()
            ->map(fn (Sancion $s) => (object) [
                'jugador' => $s->socio->nombre_completo ?? 'Socio eliminado',
                'equipo' => $s->equipo->nombre ?? '-',
                'motivo' => $s->motivo,
                'fechas' => $s->fechas,
                'restantes' => $s->fechas_restantes,
                'desde' => $s->fecha_desde,
            ])
            ->sortByDesc('restantes')
            ->values();
    }

    /**
     * Acumulacion de tarjetas del torneo, para que se vea quien esta cerca
     * de una sancion. Se ordena por lo mas grave primero.
     *
     * @return Collection<int, object>
     */
    public function tarjetas(Torneo $torneo): Collection
    {
        return $this->incidenciasDelTorneo($torneo, array_keys(IncidenciaPartido::TARJETAS))
            ->groupBy('socio_id')
            ->map(fn (Collection $tarjetas) => (object) [
                'jugador' => $tarjetas->first()->socio->nombre_completo ?? 'Socio eliminado',
                'equipo' => $tarjetas->first()->equipo->nombre ?? '-',
                'amarillas' => $tarjetas->where('tipo', IncidenciaPartido::TIPO_AMARILLA)->count(),
                'rojas' => $tarjetas->where('tipo', IncidenciaPartido::TIPO_ROJA)->count(),
            ])
            ->sortBy([
                fn ($a, $b) => $b->rojas <=> $a->rojas,
                fn ($a, $b) => $b->amarillas <=> $a->amarillas,
                fn ($a, $b) => strcasecmp($a->jugador, $b->jugador),
            ])
            ->values();
    }

    /**
     * Valla menos vencida por categoria: equipos con menos goles en contra.
     * Se calcula por equipo y no por arquero porque no se registra quien
     * ataja cada partido. Solo entran los equipos que ya jugaron, y de cada
     * categoria se muestran los mejores.
     *
     * @return Collection<string, Collection<int, array>>
     */
    public function vallaPorCategoria(Torneo $torneo, int $limite = 5): Collection
    {
        $categorias = $this->categoriasDeEquipos($torneo);

        return $this->posiciones->calcular($torneo)
            ->filter(fn (array $fila) => $fila['pj'] > 0)
            ->groupBy(fn (array $fila) => $categorias[$fila['equipo_id']] ?? $this->nombreCategoria(null))
            ->map(fn (Collection $filas) => $filas
                ->sortBy([
                    fn ($a, $b) => $a['gc'] <=> $b['gc'],
                    fn ($a, $b) => $b['pj'] <=> $a['pj'],
                    fn ($a, $b) => strcasecmp($a['equipo'], $b['equipo']),
                ])
                ->values()
                ->take($limite))
            ->sortKeys();
    }

    /**
     * Partidos ya jugados agrupados por categoria, del mas reciente al mas
     * viejo. Si los dos equipos son de categorias distintas el partido se
     * muestra aparte, para no atribuirselo a una categoria que no es.
     *
     * @return Collection<string, Collection>
     */
    public function resultadosPorCategoria(Torneo $torneo): Collection
    {
        return Partido::with(['equipoLocal:id,nombre,categoria', 'equipoVisitante:id,nombre,categoria'])
            ->where('torneo_id', $torneo->id)
            ->computables()
            ->orderByDesc('fecha_hora')
            ->get()
            ->groupBy(function (Partido $p) {
                $local = $p->equipoLocal?->categoria;
                $visitante = $p->equipoVisitante?->categoria;

                if (filled($local) && filled($visitante) && $local !== $visitante) {
                    return 'Intercategorias';
                }

                return $this->nombreCategoria($local ?: $visitante);
            })
            ->sortKeys();
    }

    /**
     * @param  array<int, string>  $tipos
     * @return Collection<int, IncidenciaPartido>
     */
    private function incidenciasDelTorneo(Torneo $torneo, array $tipos): Collection
    {
        return IncidenciaPartido::whereIn('tipo', $tipos)
            ->whereHas('partido', fn ($q) => $q->where('torneo_id', $torneo->id)->computables())
            ->with(['socio:id,nombre_completo', 'equipo:id,nombre,categoria'])
            ->get();
    }

    /**
     * Nombre de categoria de cada equipo del torneo, indexado por equipo.
     *
     * @return Collection<int, string>
     */
    private function categoriasDeEquipos(Torneo $torneo): Collection
    {
        return Equipo::where('torneo_id', $torneo->id)
            ->pluck('categoria', 'id')
            ->map(fn ($categoria) => $this->nombreCategoria($categoria));
    }

    private function nombreCategoria(?string $categoria): string
    {
        return filled($categoria) ? 'Categoria '.$categoria : 'Sin categoria';
    }
}
