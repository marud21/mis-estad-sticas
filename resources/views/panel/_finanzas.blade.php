@php
    $plata = fn ($v) => '$'.number_format((float) $v, 0, ',', '.');
    $topeMes = $finanzas['por_mes']->max('valor') ?: 1;
@endphp

<div class="card">
    <h2>&#128176; Finanzas</h2>
    <p class="panel-sub">Facturado y recaudado en el periodo. La deuda es historica.</p>

    <div class="grid-4 panel-mini">
        <div>
            <span class="mini-rotulo">Facturado</span>
            <strong>{{ $plata($finanzas['facturado_rango']) }}</strong>
            <span class="mini-nota">{{ number_format($finanzas['cargos_rango'], 0, ',', '.') }} cargos</span>
        </div>
        <div>
            <span class="mini-rotulo">Recaudado</span>
            <strong>{{ $plata($finanzas['recaudado_rango']) }}</strong>
            <span class="mini-nota">{{ number_format($finanzas['pagos_rango'], 0, ',', '.') }} pagos</span>
        </div>
        <div>
            <span class="mini-rotulo">Cumplimiento</span>
            <strong>{{ $finanzas['cumplimiento'] !== null ? $finanzas['cumplimiento'].'%' : '-' }}</strong>
            <span class="mini-nota">recaudado sobre facturado</span>
        </div>
        <div>
            <span class="mini-rotulo">Deuda acumulada</span>
            <strong class="rojo">{{ $plata($finanzas['deuda_total']) }}</strong>
            <span class="mini-nota">todo el historico</span>
        </div>
    </div>

    {{-- ---------- Recaudo mes a mes ---------- --}}
    @if ($finanzas['por_mes']->isNotEmpty())
        <h3>Recaudo por mes</h3>
        <div class="barras" role="img" aria-label="Recaudo mensual del periodo">
            @foreach ($finanzas['por_mes'] as $mes)
                <div class="barra-col" title="{{ $mes['etiqueta'] }}: {{ $plata($mes['valor']) }}">
                    <span class="barra-valor">{{ $mes['valor'] > 0 ? round($mes['valor'] / 1000000, 1).'M' : '' }}</span>
                    <div class="barra" style="height: {{ max(2, round($mes['valor'] / $topeMes * 100)) }}%"></div>
                    <span class="barra-eje">{{ $mes['etiqueta'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="grid-2" style="margin-top:18px;">
        {{-- ---------- Como pagan ---------- --}}
        <div>
            <h3>Como se paga</h3>
            @if ($finanzas['por_tipo_pago']->isEmpty())
                <p class="panel-vacio">Sin pagos en el periodo.</p>
            @else
                @php($totalPagos = $finanzas['por_tipo_pago']->sum('valor') ?: 1)
                @foreach ($finanzas['por_tipo_pago'] as $fila)
                    <div class="proporcion">
                        <div class="proporcion-cabeza">
                            <span>{{ $fila['etiqueta'] }}</span>
                            <span>{{ $plata($fila['valor']) }} &middot; {{ round($fila['valor'] / $totalPagos * 100) }}%</span>
                        </div>
                        <div class="proporcion-riel">
                            <div class="proporcion-relleno" style="width: {{ round($fila['valor'] / $totalPagos * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            @endif

            <h3 style="margin-top:18px;">Caja</h3>
            <table class="panel-tabla">
                <tr><td>Cierres en el periodo</td><td class="n">{{ $finanzas['caja']['total'] }}</td></tr>
                <tr><td>Anulados</td><td class="n">{{ $finanzas['caja']['anulados'] }}</td></tr>
                <tr><td>Ingresos registrados</td><td class="n">{{ $plata($finanzas['caja']['ingresos']) }}</td></tr>
                <tr><td>Gastos</td><td class="n">{{ $plata($finanzas['caja']['gastos']) }}</td></tr>
                @if ($finanzas['caja']['ultimo'])
                    <tr><td>Ultimo cierre</td><td class="n">{{ $finanzas['caja']['ultimo']->fecha->format('d/m/Y') }}</td></tr>
                @endif
            </table>
        </div>

        {{-- ---------- De que se factura ---------- --}}
        <div>
            <h3>De que se factura</h3>
            @if ($finanzas['por_tipo_cargo']->isEmpty())
                <p class="panel-vacio">Sin cargos en el periodo.</p>
            @else
                <div class="table-scroll">
                    <table class="panel-tabla">
                        <thead><tr><th>Concepto</th><th class="n">Cargos</th><th class="n">Valor</th></tr></thead>
                        <tbody>
                            @foreach ($finanzas['por_tipo_cargo'] as $fila)
                                <tr>
                                    <td>{{ $fila['etiqueta'] }}</td>
                                    <td class="n">{{ number_format($fila['total'], 0, ',', '.') }}</td>
                                    <td class="n">{{ $plata($fila['valor']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
