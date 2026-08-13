@extends('layouts.app')

@section('title', $equipo->nombre)

@section('content')
    <x-breadcrumbs :items="['Equipos' => route('equipos.index'), $equipo->nombre => null]" />
    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">{{ $equipo->nombre }}</h1>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('equipos.reporte', $equipo) }}">Exportar PDF</a>
                <a class="btn btn-secondary" href="{{ route('equipos.planilla-pagos', $equipo) }}">Planilla de pagos</a>
                <a class="btn btn-secondary" href="{{ route('equipos.edit', $equipo) }}">Editar</a>
                <a class="btn btn-secondary" href="{{ route('equipos.index') }}">Volver</a>
                <button type="button" class="btn" id="btn-pagos-multiples">Pagos multiples</button>
            </div>
        </div>
        <p><strong>Categoria:</strong> {{ $equipo->categoria ?? '-' }}</p>
        <p><strong>Torneo:</strong> {{ $equipo->torneo->nombre ?? 'Sin torneo asignado' }}</p>
        <p>{{ $equipo->descripcion }}</p>
    </div>

    <div class="card">
        <div id="alerta-pagos-multiples" class="alert oculto"></div>

        <div class="card-header">
            <h2 style="margin:0;">Jugadores</h2>
            <button type="button" class="btn col-pago-multiple oculto" id="btn-ejecutar-pagos">Ejecutar pagos</button>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Posicion</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th>Deuda</th>
                        <th class="col-pago-multiple oculto">Valor a pagar</th>
                        <th class="col-pago-multiple oculto">Tipo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($equipo->socios as $socio)
                        <tr data-fila-socio="{{ $socio->id }}" data-deuda="{{ $socio->deuda_total }}">
                            <td><a href="{{ route('socios.show', $socio) }}">{{ $socio->nombre_completo }}</a></td>
                            <td>{{ $socio->posicion_juego }}</td>
                            <td>{{ [1 => 'Bueno', 2 => 'Regular', 3 => 'Malo'][$socio->nivel_jugador] ?? 'Sin registrar' }}</td>
                            <td><span class="badge badge-{{ $socio->estado }}">{{ ucfirst($socio->estado) }}</span></td>
                            <td class="celda-deuda {{ $socio->deuda_total > 0 ? 'deuda-positiva' : 'deuda-cero' }}">
                                ${{ number_format($socio->deuda_total, 0, ',', '.') }}
                            </td>
                            <td class="col-pago-multiple oculto">
                                <input type="number" step="0.01" min="0.01" class="input-valor-pago" data-socio-id="{{ $socio->id }}" placeholder="Valor" style="width:110px; margin-bottom:0;">
                            </td>
                            <td class="col-pago-multiple oculto">
                                <select class="input-tipo-pago" data-socio-id="{{ $socio->id }}" style="width:auto; margin-bottom:0;">
                                    <option value="efectivo">Efectivo</option>
                                    <option value="transferencia">Transferencia</option>
                                </select>
                            </td>
                            <td>
                                <form action="{{ route('equipos.socios.destroy', [$equipo, $socio]) }}" method="POST" onsubmit="return confirm('¿Quitar jugador del equipo?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger" type="submit">Quitar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Sin jugadores asignados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Agregar jugador</h3>
        <form action="{{ route('equipos.socios.store', $equipo) }}" method="POST" style="display:flex; gap:8px; align-items:center;">
            @csrf
            <select name="socio_id" style="width:auto; margin-bottom:0;" required>
                <option value="">-- Seleccionar socio --</option>
                @foreach ($sociosDisponibles as $socio)
                    <option value="{{ $socio->id }}">
                        {{ $socio->nombre_completo }}
                        @if ($socio->equipoActual())
                            (actualmente en {{ $socio->equipoActual()->nombre }})
                        @endif
                    </option>
                @endforeach
            </select>
            <button class="btn" type="submit">Agregar</button>
        </form>
        <p style="font-size:12px; color:#666; margin-top:6px;">
            Un socio solo puede pertenecer a un equipo. Si seleccionas uno que ya esta en otro equipo, sera retirado de ese equipo automaticamente.
        </p>
    </div>

    <style>.oculto { display: none; }</style>
    <script>
        const btnPagosMultiples = document.getElementById('btn-pagos-multiples');
        const btnEjecutarPagos = document.getElementById('btn-ejecutar-pagos');
        const columnasPago = document.querySelectorAll('.col-pago-multiple');
        const alerta = document.getElementById('alerta-pagos-multiples');

        function alternarPagosMultiples() {
            columnasPago.forEach(function (col) { col.classList.toggle('oculto'); });
        }

        btnPagosMultiples.addEventListener('click', alternarPagosMultiples);

        function mostrarAlerta(texto, esError) {
            alerta.textContent = texto;
            alerta.classList.remove('oculto', 'alert-error');
            if (esError) alerta.classList.add('alert-error');
        }

        btnEjecutarPagos.addEventListener('click', function () {
            const filas = [];
            document.querySelectorAll('.input-valor-pago').forEach(function (input) {
                const valor = parseFloat(input.value);
                if (!valor || valor <= 0) return;
                const socioId = input.dataset.socioId;
                const tipo = document.querySelector('.input-tipo-pago[data-socio-id="' + socioId + '"]').value;
                filas.push({ socio_id: socioId, valor: valor, tipo: tipo });
            });

            if (filas.length === 0) {
                mostrarAlerta('Escribe al menos un valor a pagar antes de ejecutar los pagos.', true);
                return;
            }

            const conRecibo = confirm('¿Deseas imprimir automaticamente el recibo de cada pago que se registre?\n\nAceptar = con recibo. Cancelar = sin recibo.');

            btnEjecutarPagos.disabled = true;
            btnEjecutarPagos.textContent = 'Procesando...';

            fetch('{{ route('equipos.pagos.ejecutar', $equipo) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ pagos: filas }),
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    mostrarAlerta(data.mensaje, false);

                    data.pagos.forEach(function (pago) {
                        const fila = document.querySelector('[data-fila-socio="' + pago.socio_id + '"]');
                        if (!fila) return;

                        const inputValor = fila.querySelector('.input-valor-pago');
                        const valorPagado = inputValor ? parseFloat(inputValor.value || 0) : 0;
                        if (inputValor) inputValor.value = '';

                        const nuevaDeuda = Math.max(0, parseFloat(fila.dataset.deuda) - valorPagado);
                        fila.dataset.deuda = nuevaDeuda;
                        const celdaDeuda = fila.querySelector('.celda-deuda');
                        if (celdaDeuda) {
                            celdaDeuda.textContent = '$' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(nuevaDeuda);
                            celdaDeuda.classList.toggle('deuda-positiva', nuevaDeuda > 0);
                            celdaDeuda.classList.toggle('deuda-cero', nuevaDeuda <= 0);
                        }
                    });

                    if (conRecibo) {
                        data.pagos.forEach(function (pago, i) {
                            setTimeout(function () { window.open(pago.recibo_url, '_blank'); }, i * 400);
                        });
                    }
                })
                .catch(function () {
                    mostrarAlerta('Ocurrio un error al registrar los pagos. Intenta de nuevo.', true);
                })
                .finally(function () {
                    btnEjecutarPagos.disabled = false;
                    btnEjecutarPagos.textContent = 'Ejecutar pagos';
                });
        });
    </script>
@endsection
