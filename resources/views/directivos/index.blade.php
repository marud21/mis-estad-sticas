@extends('layouts.app')

@section('title', 'Cuentas de directivo')

@section('content')
    @php
        /*
         * Cual de los formularios hay que dejar abierto al volver.
         *
         * Cuando una validacion falla, Laravel devuelve a esta pantalla y
         * los formularios desplegables se cerrarian, con lo que el aviso
         * de error quedaria arriba sin que se vea a que campo se refiere.
         * Cada formulario manda su nombre y aqui se vuelve a abrir el que
         * fallo. Tambien sirve para que old() se use SOLO en ese: sin
         * esto, un alta fallida pintaria el nombre intentado en la fila de
         * todos los demas directivos.
         */
        $abierto = old('formulario');
    @endphp

    <x-breadcrumbs :items="['Cuentas de directivo' => null]" />

    <div class="card">
        <h1>Cuentas de directivo</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            El directivo entra con su propio correo y contraseña. Ve las finanzas, el sistema y las noticias;
            a los socios y los equipos los consulta pero no los modifica, y no entra al panel.
            Esta pantalla es solo suya: un directivo no puede abrirla ni cambiarle la cuenta a otro.
        </p>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo con el que entra</th>
                        <th>Cuenta creada</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($directivos as $fila)
                        @php
                            $directivo = $fila['modelo'];
                            $id = $directivo->id;
                        @endphp
                        <tr>
                            <td>{{ $directivo->name }}</td>
                            <td>
                                {{ $directivo->email }}
                                @if ($fila['sesiones'] !== null && $fila['sesiones'] > 0)
                                    <br><small style="color:#888;">
                                        {{ $fila['sesiones'] === 1 ? 'tiene una sesion abierta' : 'tiene '.$fila['sesiones'].' sesiones abiertas' }}
                                    </small>
                                @endif
                            </td>
                            <td>{{ $directivo->created_at?->format('d/m/Y') ?? '--' }}</td>
                            <td class="actions">
                                <button class="btn btn-sm btn-secondary" type="button"
                                        onclick="alternar('editar-{{ $id }}')">Editar</button>
                                <button class="btn btn-sm btn-secondary" type="button"
                                        onclick="alternar('clave-{{ $id }}')">Contraseña</button>
                                <button class="btn btn-sm btn-danger" type="button"
                                        onclick="alternar('eliminar-{{ $id }}')">Eliminar</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                No hay ninguna cuenta de directivo. Puede crear una aqui abajo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{--
            Los formularios van FUERA de la tabla a proposito.
            Puestos dentro de una fila quedaban dentro del contenedor que
            se desliza de lado en pantallas angostas, y en un telefono el
            campo de la contraseña quedaba cortado contra el borde: habia
            que arrastrar la tabla para alcanzarlo. Aqui abajo ocupan el
            ancho completo y se usan igual en el telefono que en el
            computador.
        --}}
        @foreach ($directivos as $fila)
            @php
                $directivo = $fila['modelo'];
                $id = $directivo->id;
            @endphp

            {{-- Nombre y correo --}}
            <div id="editar-{{ $id }}" class="desplegable panel-cuenta {{ $abierto === 'editar-'.$id ? '' : 'oculto' }}">
                <form action="{{ route('directivos.update', $id) }}" method="POST" style="margin:0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="formulario" value="editar-{{ $id }}">
                    <h3>Nombre y correo de {{ $directivo->name }}</h3>
                    <div class="grid-2">
                        <div>
                            <label>Nombre</label>
                            <input type="text" name="name" required maxlength="100"
                                   value="{{ $abierto === 'editar-'.$id ? old('name') : $directivo->name }}">
                        </div>
                        <div>
                            <label>Correo</label>
                            <input type="email" name="email" required
                                   value="{{ $abierto === 'editar-'.$id ? old('email') : $directivo->email }}">
                        </div>
                    </div>
                    <p style="font-size:13px; color:#666;">
                        Si le cambia el correo, desde ese momento entra con el nuevo.
                    </p>
                    <button class="btn btn-sm" type="submit">Guardar cambios</button>
                    <button class="btn btn-sm btn-secondary" type="button"
                            onclick="alternar('editar-{{ $id }}')">Cancelar</button>
                </form>
            </div>

            {{-- Contraseña nueva --}}
            <div id="clave-{{ $id }}" class="desplegable panel-cuenta {{ $abierto === 'clave-'.$id ? '' : 'oculto' }}">
                <form action="{{ route('directivos.password', $id) }}" method="POST" style="margin:0;">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="formulario" value="clave-{{ $id }}">
                    <h3>Asignarle una contraseña nueva a {{ $directivo->name }}</h3>
                    <p style="font-size:13px; color:#666; margin-top:-8px;">
                        No necesita saber la contraseña anterior. Al guardar, si {{ $directivo->name }}
                        tenia una sesion abierta se le cierra, y tendra que entrar con la nueva.
                    </p>
                    <div class="grid-2">
                        <div>
                            <label>Contraseña nueva</label>
                            <input type="password" name="password" required minlength="8"
                                   autocomplete="new-password">
                        </div>
                        <div>
                            <label>Repita la contraseña nueva</label>
                            <input type="password" name="password_confirmation" required minlength="8"
                                   autocomplete="new-password">
                        </div>
                    </div>
                    <label>Para confirmar, escriba SU contraseña de administrador</label>
                    <input type="password" name="password_admin" required
                           autocomplete="current-password" class="campo-angosto">
                    <div style="margin-top:10px;">
                        <button class="btn btn-sm" type="submit">Guardar contraseña</button>
                        <button class="btn btn-sm btn-secondary" type="button"
                                onclick="alternar('clave-{{ $id }}')">Cancelar</button>
                    </div>
                </form>
            </div>

            {{-- Eliminar --}}
            <div id="eliminar-{{ $id }}" class="desplegable panel-cuenta {{ $abierto === 'eliminar-'.$id ? '' : 'oculto' }}">
                <form action="{{ route('directivos.destroy', $id) }}" method="POST" style="margin:0;"
                      onsubmit="return confirm('¿Eliminar la cuenta de {{ addslashes($directivo->name) }}? No podra volver a entrar.');">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="formulario" value="eliminar-{{ $id }}">
                    <h3>Eliminar la cuenta de {{ $directivo->name }}</h3>
                    <div class="alert">
                        @if (empty($fila['datos']))
                            Esta cuenta no tiene nada registrado a su nombre. Al eliminarla no se
                            pierde ningun dato: simplemente deja de poder entrar.
                        @else
                            <strong>Lo que registro NO se borra.</strong> Estos registros se quedan
                            tal como estan y solo pierden el nombre de quien los hizo:
                            <ul style="margin:6px 0 0 18px;">
                                @foreach ($fila['datos'] as $etiqueta => $cuantos)
                                    <li>{{ $etiqueta }}: <strong>{{ $cuantos }}</strong></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <label>Para confirmar, escriba SU contraseña de administrador</label>
                    <input type="password" name="password_admin" required
                           autocomplete="current-password" class="campo-angosto">
                    <div style="margin-top:10px;">
                        <button class="btn btn-sm btn-danger" type="submit">Eliminar la cuenta</button>
                        <button class="btn btn-sm btn-secondary" type="button"
                                onclick="alternar('eliminar-{{ $id }}')">Cancelar</button>
                    </div>
                </form>
            </div>
        @endforeach
    </div>

    <div class="card" style="max-width: 560px;">
        <h1>Agregar un directivo</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            Entrega el correo y la contraseña a la persona. Ella puede cambiarse la contraseña
            despues desde <em>Sistema &rarr; Cambiar contraseña</em>.
        </p>
        <form action="{{ route('directivos.store') }}" method="POST">
            @csrf
            <input type="hidden" name="formulario" value="crear">

            <label>Nombre</label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ $abierto === 'crear' ? old('name') : '' }}">

            <label>Correo</label>
            <input type="email" name="email" required
                   value="{{ $abierto === 'crear' ? old('email') : '' }}">

            <label>Contraseña (al menos 8 caracteres)</label>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">

            <label>Repita la contraseña</label>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">

            <button class="btn" type="submit">Crear la cuenta</button>
        </form>
    </div>

    <style>
        .oculto { display: none; }

        .panel-cuenta {
            border: 1px solid var(--gris-borde);
            border-left: 3px solid var(--marca-oscura);
            border-radius: 6px;
            padding: 14px 16px;
            margin-top: 16px;
            background: #fbfcfd;
        }
        .panel-cuenta h3 { margin-top: 0; }

        /* El campo va corto en pantalla ancha, pero completo en el
           telefono: encogerlo ahi lo dejaria mas angosto que el dedo. */
        .campo-angosto { max-width: 320px; }
        @media (max-width: 560px) {
            .campo-angosto { max-width: 100%; }
            .panel-cuenta .grid-2 { grid-template-columns: 1fr; }
        }
    </style>

    <script>
        /*
         * Despliega un formulario y cierra los demas, para que no queden
         * tres abiertos encima de la misma cuenta.
         */
        function alternar(id) {
            var panel = document.getElementById(id);
            if (! panel) {
                return;
            }

            var abrir = panel.classList.contains('oculto');

            document.querySelectorAll('.desplegable').forEach(function (otro) {
                if (otro.id !== id) {
                    otro.classList.add('oculto');
                }
            });

            panel.classList.toggle('oculto', ! abrir);

            // En el telefono el formulario sale debajo de la tabla, que
            // puede quedar fuera de la vista: se lo trae a pantalla.
            if (abrir) {
                panel.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            }
        }
    </script>
@endsection
