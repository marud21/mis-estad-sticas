@php($topeEstado = $socios['por_estado']->max('total') ?: 1)

<div class="card">
    <h2>&#128101; Socios</h2>
    <p class="panel-sub">Foto de hoy. Estos conteos no dependen del rango de fechas.</p>

    <div class="grid-2">
        <div>
            <h3>Por estado</h3>
            @foreach ($socios['por_estado'] as $fila)
                <div class="proporcion">
                    <div class="proporcion-cabeza">
                        <span>
                            <a href="{{ route('socios.index', ['estado' => $fila['valor']]) }}">{{ $fila['etiqueta'] }}</a>
                        </span>
                        <span>{{ number_format($fila['total'], 0, ',', '.') }}</span>
                    </div>
                    <div class="proporcion-riel">
                        <div class="proporcion-relleno estado-{{ $fila['valor'] }}"
                             style="width: {{ round($fila['total'] / $topeEstado * 100) }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            <h3>Carnet</h3>
            <table class="panel-tabla">
                @foreach ($socios['por_carnet'] as $fila)
                    <tr>
                        <td>{{ $fila['etiqueta'] }}</td>
                        <td class="n">{{ number_format($fila['total'], 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </table>

            <h3 style="margin-top:18px;">Para revisar</h3>
            <table class="panel-tabla">
                <tr>
                    <td>Sin equipo (sin contar retirados)</td>
                    <td class="n">{{ number_format($socios['sin_equipo'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>En mas de un equipo</td>
                    <td class="n">{{ number_format($socios['en_varios_equipos'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Altas en el periodo</td>
                    <td class="n">{{ number_format($socios['altas_en_rango'], 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>
