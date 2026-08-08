@extends('layouts.app')

@section('title', 'Nuevo cierre de caja')

@section('content')
    <x-breadcrumbs :items="['Reportes' => route('reportes-contables.index'), 'Cierre de caja' => route('cierre-caja.index'), 'Nuevo' => null]" />

    <div class="card">
        <h1>Cierre de caja</h1>

        <form method="GET" action="{{ route('cierre-caja.create') }}" style="max-width:220px;">
            <label>Fecha del cierre</label>
            <input type="date" name="fecha" value="{{ $fecha->format('Y-m-d') }}" max="{{ date('Y-m-d') }}" onchange="this.form.submit()">
        </form>

        @if ($yaExiste)
            <div class="alert alert-error">
                Ya existe un cierre de caja guardado para el {{ $fecha->format('d/m/Y') }}.
                <a href="{{ route('cierre-caja.index') }}">Ver historial</a>.
            </div>
        @else
            <form action="{{ route('cierre-caja.store') }}" method="POST">
                @csrf
                <input type="hidden" name="fecha" value="{{ $fecha->format('Y-m-d') }}">

                <h3>Ingresos del {{ $fecha->format('d/m/Y') }}</h3>
                <p style="font-size:13px; color:#666; margin-top:-8px;">Calculados automaticamente a partir de los pagos registrados ese dia.</p>
                <div class="grid-2" style="max-width:600px;">
                    <div>
                        <label>Efectivo</label>
                        <input type="text" value="${{ number_format($ingresos['efectivo'], 0, ',', '.') }}" disabled>
                    </div>
                    <div>
                        <label>Transferencia</label>
                        <input type="text" value="${{ number_format($ingresos['transferencia'], 0, ',', '.') }}" disabled>
                    </div>
                </div>
                <p><strong>Total del dia:</strong> <span id="total-ingresos-label">${{ number_format($ingresos['total'], 0, ',', '.') }}</span></p>

                <h3>Gastos del dia</h3>
                <table id="tabla-gastos">
                    <thead>
                        <tr><th>Descripcion</th><th style="width:160px;">Monto</th><th style="width:40px;"></th></tr>
                    </thead>
                    <tbody id="gastos-body"></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-secondary" id="btn-agregar-gasto">+ Agregar gasto</button>

                <p style="margin-top:16px;"><strong>Total gastos:</strong> <span id="total-gastos-label">$0</span></p>
                <p><strong>Total neto en efectivo (efectivo - gastos):</strong> <span id="total-neto-label">${{ number_format($ingresos['efectivo'], 0, ',', '.') }}</span></p>

                <h3>Notas (opcional)</h3>
                <textarea name="notas" rows="3" maxlength="1000" placeholder="Observaciones sobre el cierre del dia"></textarea>

                <button class="btn" type="submit">Guardar cierre de caja</button>
                <a class="btn btn-secondary" href="{{ route('cierre-caja.index') }}">Cancelar</a>
            </form>
        @endif
    </div>

    <script>
        const efectivo = {{ (float) $ingresos['efectivo'] }};
        const gastosBody = document.getElementById('gastos-body');
        let indiceGasto = 0;

        function formatearMoneda(valor) {
            return '$' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(valor);
        }

        function recalcularTotales() {
            let totalGastos = 0;
            gastosBody.querySelectorAll('.gasto-monto').forEach(function (input) {
                totalGastos += parseFloat(input.value || 0);
            });
            document.getElementById('total-gastos-label').textContent = formatearMoneda(totalGastos);
            document.getElementById('total-neto-label').textContent = formatearMoneda(efectivo - totalGastos);
        }

        function agregarFilaGasto() {
            const i = indiceGasto++;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" name="gastos[${i}][descripcion]" maxlength="255" style="margin-bottom:0;"></td>
                <td><input type="number" step="0.01" min="0" name="gastos[${i}][monto]" class="gasto-monto" style="margin-bottom:0;"></td>
                <td><button type="button" class="btn btn-sm btn-danger btn-quitar-gasto">&times;</button></td>
            `;
            gastosBody.appendChild(tr);
            tr.querySelector('.gasto-monto').addEventListener('input', recalcularTotales);
            tr.querySelector('.btn-quitar-gasto').addEventListener('click', function () {
                tr.remove();
                recalcularTotales();
            });
        }

        document.getElementById('btn-agregar-gasto').addEventListener('click', agregarFilaGasto);
        agregarFilaGasto();
    </script>
@endsection
