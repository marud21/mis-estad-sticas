{{--
    Secciones de estadisticas de un torneo (portada publica).

    Las listas largas se cortan en las primeras filas y el resto queda
    oculto tras un boton "Ver mas", para que la portada no se alargue sin
    control. Todo viene ya en el HTML, asi que desplegar no recarga nada.
--}}
@php($tope = 10)

{{-- ---------- Posiciones por categoria ---------- --}}
<div class="card">
    <h2>&#127942; Tabla de posiciones</h2>

    @forelse ($posicionesPorCategoria as $categoria => $filas)
        <h3>{{ $categoria }}</h3>
        <div class="tabla-scroll">
            <table>
                <thead>
                    <tr>
                        <th class="c">#</th>
                        <th>Equipo</th>
                        <th class="c" title="Partidos jugados">PJ</th>
                        <th class="c" title="Ganados">PG</th>
                        <th class="c" title="Empatados">PE</th>
                        <th class="c" title="Perdidos">PP</th>
                        <th class="c" title="Goles a favor">GF</th>
                        <th class="c" title="Goles en contra">GC</th>
                        <th class="c" title="Diferencia de gol">DG</th>
                        <th class="c">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr class="{{ $fila['posicion'] === 1 ? 'lider' : '' }}">
                            <td class="c"><span class="puesto">{{ $fila['posicion'] }}</span></td>
                            <td>{{ $fila['equipo'] }}</td>
                            <td class="c">{{ $fila['pj'] }}</td>
                            <td class="c">{{ $fila['pg'] }}</td>
                            <td class="c">{{ $fila['pe'] }}</td>
                            <td class="c">{{ $fila['pp'] }}</td>
                            <td class="c">{{ $fila['gf'] }}</td>
                            <td class="c">{{ $fila['gc'] }}</td>
                            <td class="c">{{ $fila['dg'] > 0 ? '+' : '' }}{{ $fila['dg'] }}</td>
                            <td class="c destacado">{{ $fila['pts'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p class="vacio">Este torneo aun no tiene equipos inscritos.</p>
    @endforelse
</div>

<div class="rejilla">
    {{-- ---------- Goleadores por categoria ---------- --}}
    <div class="card">
        <h2>&#9917; Goleadores</h2>
        @forelse ($goleadoresPorCategoria as $categoria => $lista)
            @php($id = 'goleadores-'.\Illuminate\Support\Str::slug($categoria))
            <h3>{{ $categoria }}</h3>
            <div class="tabla-scroll">
                <table data-lista="{{ $id }}">
                    <thead>
                        <tr><th class="c">#</th><th>Jugador</th><th>Equipo</th><th class="c">Goles</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($lista as $i => $g)
                            <tr class="{{ $i === 0 ? 'lider' : '' }} {{ $i >= $tope ? 'extra oculto' : '' }}">
                                <td class="c"><span class="puesto">{{ $i + 1 }}</span></td>
                                <td>{{ $g->jugador }}</td>
                                <td>{{ $g->equipo }}</td>
                                <td class="c destacado">{{ $g->goles }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.ver-mas', ['lista' => $id, 'total' => $lista->count(), 'tope' => $tope])
        @empty
            <p class="vacio">Aun no se han registrado goles.</p>
        @endforelse
    </div>

    {{-- ---------- Valla menos vencida por categoria ---------- --}}
    <div class="card">
        <h2>&#128737; Valla menos vencida</h2>
        @forelse ($vallaPorCategoria as $categoria => $filas)
            <h3>{{ $categoria }}</h3>
            <div class="tabla-scroll">
                <table>
                    <thead>
                        <tr><th class="c">#</th><th>Equipo</th><th class="c">PJ</th><th class="c">Goles recibidos</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($filas as $i => $fila)
                            <tr class="{{ $i === 0 ? 'lider' : '' }}">
                                <td class="c"><span class="puesto">{{ $i + 1 }}</span></td>
                                <td>{{ $fila['equipo'] }}</td>
                                <td class="c">{{ $fila['pj'] }}</td>
                                <td class="c destacado">{{ $fila['gc'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @empty
            <p class="vacio">Aun no hay partidos jugados.</p>
        @endforelse
        @if ($vallaPorCategoria->isNotEmpty())
            <p class="sub" style="margin:10px 0 0;">Se calcula por equipo, segun los goles recibidos.</p>
        @endif
    </div>
</div>

{{-- ---------- Sancionados por la corporacion ---------- --}}
<div class="card">
    <h2>&#9878; Sancionados</h2>
    @if ($sancionados->isEmpty())
        <p class="vacio">No hay jugadores sancionados en este torneo.</p>
    @else
        <p class="sub">Jugadores con fechas de sancion por cumplir.</p>
        <div class="tabla-scroll">
            <table data-lista="sancionados">
                <thead>
                    <tr><th>Jugador</th><th>Equipo</th><th>Motivo</th><th class="c">Fechas</th><th class="c">Le faltan</th></tr>
                </thead>
                <tbody>
                    @foreach ($sancionados as $i => $s)
                        <tr class="{{ $i >= $tope ? 'extra oculto' : '' }}">
                            <td>{{ $s->jugador }}</td>
                            <td>{{ $s->equipo }}</td>
                            <td>{{ $s->motivo }}</td>
                            <td class="c">{{ $s->fechas }}</td>
                            <td class="c"><span class="roja">{{ $s->restantes }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.ver-mas', ['lista' => 'sancionados', 'total' => $sancionados->count(), 'tope' => $tope])
    @endif
</div>

{{-- ---------- Tarjetas acumuladas ---------- --}}
<div class="card">
    <h2>&#128997; Tarjetas</h2>
    @if ($tarjetas->isEmpty())
        <p class="vacio">No hay jugadores con tarjetas en este torneo.</p>
    @else
        <div class="tabla-scroll">
            <table data-lista="tarjetas">
                <thead>
                    <tr><th>Jugador</th><th>Equipo</th><th class="c">Amarillas</th><th class="c">Rojas</th></tr>
                </thead>
                <tbody>
                    @foreach ($tarjetas as $i => $t)
                        <tr class="{{ $i >= $tope ? 'extra oculto' : '' }}">
                            <td>{{ $t->jugador }}</td>
                            <td>{{ $t->equipo }}</td>
                            <td class="c">
                                @if ($t->amarillas) <span class="amarilla">{{ $t->amarillas }}</span> @else - @endif
                            </td>
                            <td class="c">
                                @if ($t->rojas) <span class="roja">{{ $t->rojas }}</span> @else - @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.ver-mas', ['lista' => 'tarjetas', 'total' => $tarjetas->count(), 'tope' => $tope])
    @endif
</div>

{{-- ---------- Resultados por categoria ---------- --}}
<div class="card">
    <h2>&#128197; Resultados</h2>
    @forelse ($resultadosPorCategoria as $categoria => $partidos)
        @php($lista = 'resultados-'.\Illuminate\Support\Str::slug($categoria))
        <h3>{{ $categoria }}</h3>
        <div class="tabla-scroll">
            <table data-lista="{{ $lista }}">
                <thead>
                    <tr><th>Fecha</th><th>Fase</th><th>Local</th><th class="c">Marcador</th><th>Visitante</th></tr>
                </thead>
                <tbody>
                    @foreach ($partidos as $i => $p)
                        <tr class="{{ $i >= $tope ? 'extra oculto' : '' }}">
                            <td style="white-space:nowrap;">{{ $p->fecha_hora->format('d/m/Y') }}</td>
                            <td>{{ $p->fase ?? '-' }}</td>
                            <td class="{{ $p->goles_local > $p->goles_visitante ? 'destacado' : '' }}">{{ $p->equipoLocal->nombre ?? '-' }}</td>
                            <td class="c marcador">{{ $p->goles_local }} - {{ $p->goles_visitante }}</td>
                            <td class="{{ $p->goles_visitante > $p->goles_local ? 'destacado' : '' }}">{{ $p->equipoVisitante->nombre ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('partials.ver-mas', ['lista' => $lista, 'total' => $partidos->count(), 'tope' => $tope])
    @empty
        <p class="vacio">Aun no hay partidos jugados.</p>
    @endforelse
</div>
