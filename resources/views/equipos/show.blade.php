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
                <button type="button" class="btn" id="btn-cobro-tarjetas">Cobro de tarjetas</button>
            </div>
        </div>
        <p><strong>Categoria:</strong> {{ $equipo->categoria ?? '-' }}</p>
        <p><strong>Torneo:</strong> {{ $equipo->torneo->nombre ?? 'Sin torneo asignado' }}</p>
        <p>{{ $equipo->descripcion }}</p>

        <p>
            <strong>Estado:</strong>
            <span class="badge badge-{{ $equipo->estado }}">{{ ucfirst($equipo->estado) }}</span>
            @if ($equipo->fecha_cambio_estado)
                <span style="color:#666; font-size:13px;">(desde {{ $equipo->fecha_cambio_estado->format('d/m/Y') }})</span>
            @endif
        </p>
        <form id="form-cambiar-estado" action="{{ route('equipos.estado', $equipo) }}" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; max-width:320px;"
              onsubmit="return confirm(this.querySelector('select').value === 'inactivo' ? '¿Marcar el equipo como inactivo? Sus socios activos quedaran suspendidos automaticamente.' : '¿Marcar el equipo como activo? Los socios suspendidos por el equipo se reactivaran automaticamente.');">
            @csrf
            @method('PATCH')
            <select name="estado" style="width:auto; margin-bottom:0;">
                <option value="activo" @selected($equipo->estado === 'activo')>Activo</option>
                <option value="inactivo" @selected($equipo->estado === 'inactivo')>Inactivo</option>
            </select>
            <button class="btn btn-sm" type="submit">Cambiar estado</button>
        </form>
    </div>

    <div class="card">
        <div id="alerta-pagos-multiples" class="alert oculto"></div>

        <div class="card-header">
            <h2 style="margin:0;">
                Jugadores
                <span style="font-weight:normal; color:#666; font-size:12px; margin-left:6px;">
                    (orden alfabetico) &middot; Carnet: ✅ Tiene &nbsp; ➖ Extraviado &nbsp; ❌ No tiene
                </span>
            </h2>
            <button type="button" class="btn col-pago-multiple oculto" id="btn-ejecutar-pagos">Ejecutar pagos</button>
            <button type="button" class="btn col-tarjeta oculto" id="btn-registrar-tarjetas">Registrar tarjetas</button>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Posicion</th>
                        <th>Nivel</th>
                        <th>Estado</th>
                        <th style="text-align:center;">Carnet</th>
                        <th>Deuda</th>
                        <th class="col-pago-multiple oculto">Valor a pagar</th>
                        <th class="col-pago-multiple oculto">Tipo</th>
                        <th class="col-tarjeta oculto">Tarjeta</th>
                        <th class="col-tarjeta oculto">Fecha de la tarjeta</th>
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
                            <td style="text-align:center; font-size:16px;" title="Carnet: {{ $socio->carnet_etiqueta }}">{{ $socio->carnet_simbolo }}</td>
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
                            <td class="col-tarjeta oculto">
                                <select class="input-tipo-tarjeta" data-socio-id="{{ $socio->id }}" style="width:auto; margin-bottom:0;">
                                    <option value="">-- Sin tarjeta --</option>
                                    @foreach ($tiposTarjeta as $tipoTarjeta)
                                        <option value="{{ $tipoTarjeta->id }}" data-monto="{{ $tipoTarjeta->monto_default }}">
                                            {{ $tipoTarjeta->nombre }} (${{ number_format($tipoTarjeta->monto_default, 0, ',', '.') }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="col-tarjeta oculto">
                                <input type="date" class="input-fecha-tarjeta" data-socio-id="{{ $socio->id }}" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" style="width:auto; margin-bottom:0;">
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
                        <tr><td colspan="11">Sin jugadores asignados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Agregar jugador</h3>
        <form id="form-agregar-jugador" action="{{ route('equipos.socios.store', $equipo) }}" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            @csrf
            <select name="socio_id" style="width:auto; max-width:100%; margin-bottom:0;" required>
                <option value="">-- Seleccionar socio --</option>
                @foreach ($sociosDisponibles as $socio)
                    <option value="{{ $socio->id }}">
                        {{ $socio->nombre_completo }}
                        @if ($socio->equipos->isNotEmpty())
                            (tambien en: {{ $socio->equipos->pluck('nombre')->join(', ') }})
                        @endif
                    </option>
                @endforeach
            </select>
            <button class="btn" type="submit">Agregar</button>
        </form>
        <p style="font-size:12px; color:#666; margin-top:6px;">
            Un socio puede pertenecer a varios equipos a la vez. Si ya esta en otro equipo, se agregara a este tambien sin quitarlo del anterior.
        </p>
    </div>

    <style>
        .oculto { display: none; }

        @media (max-width: 600px) {
            #form-cambiar-estado { max-width: 100%; }
            #form-cambiar-estado select,
            #form-agregar-jugador select {
                width: 100%;
                max-width: 100%;
            }
            #form-agregar-jugador { flex-direction: column; align-items: stretch; }
            td.col-pago-multiple { min-width: 130px; }
            td.col-tarjeta { min-width: 150px; }
        }
    </style>
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

        /**
         * Si la sesion vencio, el servidor responde 419 con el aviso y la
         * direccion del login. Se muestra con un enlace en vez de redirigir
         * de golpe, para no borrarle al usuario lo que ya habia escrito.
         * Devuelve true si la sesion estaba vencida.
         */
        function avisarSiSesionExpiro(respuesta, datos) {
            if (respuesta.status !== 419) return false;

            const texto = (datos && datos.mensaje) || 'Tu sesion expiro por seguridad.';
            const url = (datos && datos.login_url) || '{{ route('login') }}';

            alerta.textContent = texto + ' ';
            alerta.classList.remove('oculto');
            alerta.classList.add('alert-error');

            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.textContent = 'Iniciar sesion';
            alerta.appendChild(enlace);

            return true;
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
                .then(function (r) { return r.json().then(function (d) { return { respuesta: r, datos: d }; }); })
                .then(function (res) {
                    if (avisarSiSesionExpiro(res.respuesta, res.datos)) return;

                    const data = res.datos;
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

        /**
         * Cobro de tarjetas: muestra frente a cada jugador un desplegable
         * con los tipos de tarjeta (Amarillas/Rojas) y la fecha en que se
         * saco. Al registrar, cada tarjeta marcada se convierte en un cargo
         * para ese socio, por el monto del tipo elegido.
         */
        const btnCobroTarjetas = document.getElementById('btn-cobro-tarjetas');
        const btnRegistrarTarjetas = document.getElementById('btn-registrar-tarjetas');
        const columnasTarjeta = document.querySelectorAll('.col-tarjeta');

        btnCobroTarjetas.addEventListener('click', function () {
            columnasTarjeta.forEach(function (col) { col.classList.toggle('oculto'); });
        });

        btnRegistrarTarjetas.addEventListener('click', function () {
            const filasTarjeta = [];
            document.querySelectorAll('.input-tipo-tarjeta').forEach(function (select) {
                if (!select.value) return;
                const socioId = select.dataset.socioId;
                const fecha = document.querySelector('.input-fecha-tarjeta[data-socio-id="' + socioId + '"]').value;
                if (!fecha) return;
                filasTarjeta.push({ socio_id: socioId, tipo_cargo_id: select.value, fecha: fecha });
            });

            if (filasTarjeta.length === 0) {
                mostrarAlerta('Selecciona al menos una tarjeta (y su fecha) antes de registrar.', true);
                return;
            }

            if (!confirm('¿Registrar ' + filasTarjeta.length + ' tarjeta(s)? Se le sumara el cargo correspondiente a cada jugador seleccionado.')) {
                return;
            }

            btnRegistrarTarjetas.disabled = true;
            btnRegistrarTarjetas.textContent = 'Procesando...';

            fetch('{{ route('equipos.tarjetas.ejecutar', $equipo) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ tarjetas: filasTarjeta }),
            })
                .then(function (r) { return r.json().then(function (d) { return { respuesta: r, datos: d }; }); })
                .then(function (res) {
                    if (avisarSiSesionExpiro(res.respuesta, res.datos)) return;

                    const data = res.datos;
                    if (!data.cargos) {
                        mostrarAlerta('No se pudieron registrar las tarjetas. Revisa los datos e intenta de nuevo.', true);
                        return;
                    }

                    mostrarAlerta(data.mensaje, false);

                    data.cargos.forEach(function (cargo) {
                        const fila = document.querySelector('[data-fila-socio="' + cargo.socio_id + '"]');
                        if (!fila) return;

                        const nuevaDeuda = parseFloat(fila.dataset.deuda) + parseFloat(cargo.monto);
                        fila.dataset.deuda = nuevaDeuda;
                        const celdaDeuda = fila.querySelector('.celda-deuda');
                        if (celdaDeuda) {
                            celdaDeuda.textContent = '$' + new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }).format(nuevaDeuda);
                            celdaDeuda.classList.toggle('deuda-positiva', nuevaDeuda > 0);
                            celdaDeuda.classList.toggle('deuda-cero', nuevaDeuda <= 0);
                        }

                        const selectTarjeta = fila.querySelector('.input-tipo-tarjeta');
                        if (selectTarjeta) selectTarjeta.value = '';
                    });
                })
                .catch(function () {
                    mostrarAlerta('Ocurrio un error al registrar las tarjetas. Intenta de nuevo.', true);
                })
                .finally(function () {
                    btnRegistrarTarjetas.disabled = false;
                    btnRegistrarTarjetas.textContent = 'Registrar tarjetas';
                });
        });
    </script>
@endsection
