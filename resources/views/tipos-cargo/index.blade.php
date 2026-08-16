@extends('layouts.app')

@section('title', 'Tipos de cargo')

@section('content')
    <x-breadcrumbs :items="['Tipos de cargo' => null]" />
    <div class="card">
        <div class="card-header" style="margin-bottom: 16px;">
            <h1 style="margin:0;">Tipos de cargo</h1>
            <a href="{{ route('tipos-cargo.create') }}" class="btn">+ Nuevo tipo de cargo</a>
        </div>
        <table>
            <thead>
                <tr><th>Nombre</th><th>Monto por defecto</th><th>Recurrente</th><th>% suspendido</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($tiposCargo as $tipo)
                    <tr>
                        <td>
                            {{ $tipo->nombre }}
                            @if ($tipo->ya_aplicado_este_mes ?? false)
                                <span class="badge badge-suspendido" style="margin-left:6px;">Ya cargada este mes</span>
                            @endif
                        </td>
                        <td>${{ number_format($tipo->monto_default, 0, ',', '.') }}</td>
                        <td>{{ $tipo->es_recurrente ? 'Si' : 'No' }}</td>
                        <td>{{ number_format($tipo->porcentaje_suspendido, 0) }}%</td>
                        <td class="actions">
                            @if ($tipo->es_recurrente)
                                <button type="button" class="btn btn-sm" onclick="document.getElementById('panel-cargo-{{ $tipo->id }}').classList.toggle('oculto')">Gestionar cargo</button>
                            @endif
                            <a class="btn btn-sm btn-secondary" href="{{ route('tipos-cargo.edit', $tipo) }}">Editar</a>
                            <form action="{{ route('tipos-cargo.destroy', $tipo) }}" method="POST" onsubmit="return confirm('¿Eliminar este tipo de cargo?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @if ($tipo->es_recurrente)
                        <tr id="panel-cargo-{{ $tipo->id }}" class="oculto">
                            <td colspan="5">
                                <div style="margin-bottom:18px;">
                                    <h4 style="margin:0 0 4px;">Aplicar cargo nuevo</h4>
                                    <p style="font-size:12px; color:#666; margin:0 0 8px;">Crea el cargo para los socios que aun no lo tengan registrado en esa fecha.</p>
                                    <form class="form-nivel" action="{{ route('tipos-cargo.aplicar-masivo', $tipo) }}" method="POST"
                                          onsubmit="return confirmarCargaMasiva(this, {{ ($tipo->ya_aplicado_este_mes ?? false) ? 'true' : 'false' }}, '{{ $tipo->nombre }}', '{{ $tipo->porcentaje_suspendido }}', 'aplicar');">
                                        @csrf
                                        <div class="grid-4">
                                            <div>
                                                <label>Fecha</label>
                                                <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div>
                                                <label>Monto (opcional)</label>
                                                <input type="number" step="0.01" min="0" name="monto" placeholder="${{ number_format($tipo->monto_default, 0, ',', '.') }} por defecto">
                                            </div>
                                            <div>
                                                <label>Aplicar a</label>
                                                <select name="nivel" class="select-nivel">
                                                    <option value="todos">Todos los socios</option>
                                                    <option value="equipo">Un equipo especifico</option>
                                                    <option value="categoria">Una categoria especifica</option>
                                                </select>
                                            </div>
                                            <div class="campo-equipo oculto">
                                                <label>Equipo</label>
                                                <select name="equipo_id">
                                                    <option value="">-- Selecciona --</option>
                                                    @foreach ($equipos as $equipo)
                                                        <option value="{{ $equipo->id }}">{{ $equipo->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="campo-categoria oculto">
                                                <label>Categoria</label>
                                                <select name="categoria">
                                                    <option value="">-- Selecciona --</option>
                                                    @foreach ($categorias as $categoria)
                                                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm" type="submit">Aplicar cargo</button>
                                    </form>
                                </div>

                                <hr style="border:none; border-top:1px solid var(--gris-borde); margin:16px 0;">

                                <div>
                                    <h4 style="margin:0 0 4px;">Modificar cargo ya aplicado</h4>
                                    <p style="font-size:12px; color:#666; margin:0 0 8px;">
                                        Cambia el monto de un cargo que ya se le monto a los socios (ej. se les cobro el mes completo y se decidio reducirlo).
                                        Busca por la misma fecha con la que se aplico.
                                    </p>
                                    <form class="form-nivel" action="{{ route('tipos-cargo.modificar-masivo', $tipo) }}" method="POST"
                                          onsubmit="return confirmarCargaMasiva(this, false, '{{ $tipo->nombre }}', '{{ $tipo->porcentaje_suspendido }}', 'modificar');">
                                        @csrf
                                        <div class="grid-4">
                                            <div>
                                                <label>Fecha del cargo ya aplicado</label>
                                                <input type="date" name="fecha" value="{{ date('Y-m-d') }}" required>
                                            </div>
                                            <div>
                                                <label>Nuevo monto</label>
                                                <input type="number" step="0.01" min="0" name="monto" required>
                                            </div>
                                            <div>
                                                <label>Modificar en</label>
                                                <select name="nivel" class="select-nivel">
                                                    <option value="todos">Todos los socios</option>
                                                    <option value="equipo">Un equipo especifico</option>
                                                    <option value="categoria">Una categoria especifica</option>
                                                </select>
                                            </div>
                                            <div class="campo-equipo oculto">
                                                <label>Equipo</label>
                                                <select name="equipo_id">
                                                    <option value="">-- Selecciona --</option>
                                                    @foreach ($equipos as $equipo)
                                                        <option value="{{ $equipo->id }}">{{ $equipo->nombre }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="campo-categoria oculto">
                                                <label>Categoria</label>
                                                <select name="categoria">
                                                    <option value="">-- Selecciona --</option>
                                                    @foreach ($categorias as $categoria)
                                                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-secondary" type="submit">Modificar cargo</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="5">No hay tipos de cargo registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <style>.oculto { display: none; }</style>
    <script>
        function confirmarCargaMasiva(form, yaAplicado, nombre, porcentaje, modo) {
            const nivel = form.querySelector('select[name="nivel"]').value;
            const alcance = nivel === 'equipo'
                ? 'al equipo seleccionado'
                : (nivel === 'categoria' ? 'a la categoria seleccionada' : 'a todos los socios activos y suspendidos');

            if (modo === 'modificar') {
                return confirm(
                    '¿Modificar el monto del cargo "' + nombre + '" ' + alcance + ' para la fecha indicada ' +
                    '(los suspendidos quedaran al ' + porcentaje + '% del nuevo monto)?'
                );
            }

            if (yaAplicado) {
                return confirm(
                    'Advertencia: la mensualidad "' + nombre + '" ya se cargo este mes.\n' +
                    '¿Esta seguro de que desea continuar y aplicarla de nuevo (' + alcance + ')?'
                );
            }
            return confirm(
                '¿Aplicar "' + nombre + '" ' + alcance + ' (los suspendidos pagan el ' + porcentaje + '%)?'
            );
        }

        document.querySelectorAll('.select-nivel').forEach(function (select) {
            select.addEventListener('change', function () {
                const form = this.closest('.form-nivel');
                const campoEquipo = form.querySelector('.campo-equipo');
                const campoCategoria = form.querySelector('.campo-categoria');

                campoEquipo.classList.add('oculto');
                campoCategoria.classList.add('oculto');
                campoEquipo.querySelector('select').required = false;
                campoCategoria.querySelector('select').required = false;

                if (this.value === 'equipo') {
                    campoEquipo.classList.remove('oculto');
                    campoEquipo.querySelector('select').required = true;
                } else if (this.value === 'categoria') {
                    campoCategoria.classList.remove('oculto');
                    campoCategoria.querySelector('select').required = true;
                }
            });
        });
    </script>
@endsection
