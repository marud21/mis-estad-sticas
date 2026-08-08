@extends('layouts.app')

@section('title', 'Reportes de contabilidad')

@section('content')
    <x-breadcrumbs :items="['Reportes' => null]" />

    <div class="card">
        <h1>Reportes de contabilidad</h1>
        <p style="color:#666; font-size:14px;">Exporta un PDF con todos los cargos y pagos registrados en el periodo seleccionado.</p>

        <form action="{{ route('reportes-contables.exportar') }}" method="POST">
            @csrf
            <label>Tipo de reporte</label>
            <select name="tipo" id="tipo-reporte" required>
                <option value="dia">Dia especifico</option>
                <option value="mes">Mensual</option>
                <option value="rango">Rango de fechas</option>
            </select>

            <div id="campo-dia">
                <label>Fecha</label>
                <input type="date" name="fecha" value="{{ date('Y-m-d') }}">
            </div>

            <div id="campo-mes" style="display:none;">
                <label>Mes</label>
                <input type="month" name="mes" value="{{ date('Y-m') }}">
            </div>

            <div id="campo-rango" style="display:none;">
                <div class="grid-2">
                    <div>
                        <label>Desde</label>
                        <input type="date" name="fecha_inicio">
                    </div>
                    <div>
                        <label>Hasta</label>
                        <input type="date" name="fecha_fin">
                    </div>
                </div>
            </div>

            <div style="display:flex; gap:8px;">
                <button class="btn" type="submit" formaction="{{ route('reportes-contables.exportar') }}">Exportar PDF</button>
                <button class="btn btn-secondary" type="submit" formaction="{{ route('reportes-contables.exportar-excel') }}">Exportar Excel (CSV)</button>
            </div>
        </form>
    </div>

    <script>
        const tipoSelect = document.getElementById('tipo-reporte');
        const campos = {
            dia: document.getElementById('campo-dia'),
            mes: document.getElementById('campo-mes'),
            rango: document.getElementById('campo-rango'),
        };

        function actualizarCampos() {
            Object.keys(campos).forEach(function (key) {
                campos[key].style.display = key === tipoSelect.value ? 'block' : 'none';
            });
        }

        tipoSelect.addEventListener('change', actualizarCampos);
        actualizarCampos();
    </script>
@endsection
