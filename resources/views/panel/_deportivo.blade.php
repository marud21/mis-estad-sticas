@php($plata = fn ($v) => '$'.number_format((float) $v, 0, ',', '.'))

<div class="card">
    <h2>&#9917; Deportivo</h2>
    <p class="panel-sub">Partidos y estadisticas del periodo seleccionado.</p>

    <div class="grid-5 panel-mini">
        <div>
            <span class="mini-rotulo">Equipos</span>
            <strong>{{ $deportivo['equipos_activos'] }}</strong>
            <span class="mini-nota">activos de {{ $deportivo['equipos_total'] }}</span>
        </div>
        <div>
            <span class="mini-rotulo">Jugados</span>
            <strong>{{ $deportivo['jugados'] }}</strong>
            <span class="mini-nota">{{ $deportivo['programados'] }} por jugar</span>
        </div>
        <div>
            <span class="mini-rotulo">Goles</span>
            <strong>{{ $deportivo['goles'] }}</strong>
            <span class="mini-nota">
                {{ $deportivo['jugados'] > 0 ? round($deportivo['goles'] / $deportivo['jugados'], 1) : 0 }} por partido
            </span>
        </div>
        <div>
            <span class="mini-rotulo">Tarjetas</span>
            <strong>
                <span class="amarilla">{{ $deportivo['amarillas'] }}</span> /
                <span class="rojo">{{ $deportivo['rojas'] }}</span>
            </strong>
            <span class="mini-nota">amarillas / rojas</span>
        </div>
        <div>
            <span class="mini-rotulo">Sanciones vigentes</span>
            <strong>{{ $deportivo['sanciones_vigentes'] }}</strong>
            <span class="mini-nota">
                @if ($deportivo['walkovers'] > 0)
                    {{ $deportivo['walkovers'] }} walkover(s)
                @else
                    sin walkovers
                @endif
            </span>
        </div>
    </div>

    <div class="grid-2" style="margin-top:18px;">
        <div>
            <h3>Goleadores del periodo</h3>
            @if ($deportivo['goleadores']->isEmpty())
                <p class="panel-vacio">Aun no se han registrado goles en este periodo.</p>
            @else
                <table class="panel-tabla">
                    @foreach ($deportivo['goleadores'] as $g)
                        <tr>
                            <td>{{ $g->nombre_completo }}</td>
                            <td class="n">{{ $g->goles }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>

        <div>
            <h3>Equipos con mas deuda</h3>
            @if ($deportivo['equipos_mas_deben']->isEmpty())
                <p class="panel-vacio">Ningun equipo tiene deuda pendiente.</p>
            @else
                <table class="panel-tabla">
                    @foreach ($deportivo['equipos_mas_deben'] as $fila)
                        <tr>
                            <td>{{ $fila['equipo'] }}</td>
                            <td class="n rojo">{{ $plata($fila['deuda']) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </div>
    </div>
</div>
