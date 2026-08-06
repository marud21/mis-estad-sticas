@extends('layouts.app')

@section('title', 'Importar desde Excel')

@section('content')
    <x-breadcrumbs :items="['Importar' => null]" />
    <div class="card">
        <h1>Importar informacion desde Excel</h1>
        <p style="color:#666; font-size:14px;">
            Exporta cada hoja de tu Excel como CSV (Archivo &rarr; Guardar como &rarr; CSV) sin cambiar los nombres de las columnas, y subela aqui.
            Importa primero <strong>Socios</strong>, luego <strong>Cargos</strong> y <strong>Pagos</strong> (estos dos necesitan que el socio ya exista).
        </p>
    </div>

    @if (session('resultado'))
        @php $resultado = session('resultado'); @endphp
        <div class="card">
            <h2>Resultado de la importacion ({{ session('seccion') }})</h2>
            <p class="deuda-cero">{{ $resultado->exitosos }} fila(s) importada(s) correctamente.</p>
            @if ($resultado->totalFilasConError() > 0)
                <p class="deuda-positiva">{{ $resultado->totalFilasConError() }} fila(s) con error:</p>
                <ul style="font-size:13px; color:#b3261e;">
                    @foreach ($resultado->errores as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="card">
        <h2>1. Socios</h2>
        <p style="font-size:13px; color:#666;">
            Columnas esperadas: Cedula, Nombre, Fecha de nacimiento, RH, Direccion, Contacto, Equipo, Posicion de Juego, Nivel, ESTADO, FECHA DE INGRESO, ENTIDAD DE SALUD.
            Si la cedula ya existe, se actualiza el socio en vez de duplicarlo.
        </p>
        <form action="{{ route('importar.socios') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="archivo" accept=".csv,.txt" required>
            <button class="btn" type="submit">Importar socios</button>
        </form>
    </div>

    <div class="card">
        <h2>2. Cargos</h2>
        <p style="font-size:13px; color:#666;">
            Columnas esperadas: DOCUMENTO, DEUDA_ANTIGUA, AFILIACION, ASAMBLEA, INSCRIPCION1, INSCRIPCION2, ENERO...DICIEMBRE, AMARILLAS, ROJAS.
            Solo se crea un cargo si la celda tiene un valor mayor a 0. El socio debe existir previamente.
        </p>
        <form action="{{ route('importar.cargos') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label>Año de las mensualidades (columnas Enero a Diciembre)</label>
            <input type="number" name="anio" value="{{ date('Y') }}" style="width:120px;" required>
            <input type="file" name="archivo" accept=".csv,.txt" required>
            <button class="btn" type="submit">Importar cargos</button>
        </form>
    </div>

    <div class="card">
        <h2>3. Pagos</h2>
        <p style="font-size:13px; color:#666;">
            Columnas esperadas: DOCUMENTO, FECHA, TIPO PAGO (Efectivo/Transferencia), EQUIPO, VALOR. El socio debe existir previamente.
        </p>
        <form action="{{ route('importar.pagos') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="archivo" accept=".csv,.txt" required>
            <button class="btn" type="submit">Importar pagos</button>
        </form>
    </div>
@endsection
