@extends('layouts.app')

@section('title', 'Configuracion')

@section('content')
    <x-breadcrumbs :items="['Configuracion' => null]" />

    <div class="card" style="max-width: 480px;">
        <h1>Nombre y logo del sistema</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            Este nombre y logo se muestran en el encabezado, la pantalla de inicio de sesion, la pagina principal, la consulta publica y los recibos de pago.
        </p>
        <form action="{{ route('configuracion.nombre') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label>Nombre del sistema</label>
            <input type="text" name="nombre_sistema" value="{{ old('nombre_sistema', $nombreSistema) }}" required>

            <label>Descripcion de la pagina principal publica</label>
            <textarea name="descripcion_portada" rows="4" maxlength="500">{{ old('descripcion_portada', $descripcionPortada) }}</textarea>

            <label>Logo</label>
            @if ($logoUrl)
                <div style="margin-bottom:10px;">
                    <img src="{{ $logoUrl }}" alt="Logo actual" style="height:56px; width:56px; object-fit:contain; border:1px solid var(--gris-borde); border-radius:6px; padding:4px;">
                </div>
            @endif
            <input type="file" name="logo" accept="image/*">
            <button class="btn" type="submit">Guardar</button>
        </form>
    </div>

    <div class="card" style="max-width: 480px;">
        <h1>WhatsApp de la corporacion</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            Este numero se mostrara a los socios en la consulta publica, para que envien el comprobante de sus pagos virtuales.
        </p>
        <form action="{{ route('configuracion.whatsapp') }}" method="POST">
            @csrf
            @method('PUT')
            <label>Numero de WhatsApp (con indicativo de pais)</label>
            <input type="text" name="whatsapp_corporacion" placeholder="Ej: 573001234567" value="{{ old('whatsapp_corporacion', $whatsappCorporacion) }}">
            <button class="btn" type="submit">Guardar</button>
        </form>
    </div>

    <div class="card">
        <h1>Cuentas bancarias</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            Estas cuentas se mostraran a los socios en la consulta publica para que hagan sus transferencias y pagos.
        </p>
        <table>
            <thead>
                <tr><th>Banco</th><th>Tipo de cuenta</th><th>Numero</th><th>Titular</th><th></th></tr>
            </thead>
            <tbody>
                @forelse ($cuentasBancarias as $cuenta)
                    <tr>
                        <td>{{ $cuenta->banco }}</td>
                        <td>{{ $cuenta->tipo_cuenta }}</td>
                        <td>{{ $cuenta->numero_cuenta }}</td>
                        <td>{{ $cuenta->titular }}</td>
                        <td class="actions">
                            <button class="btn btn-sm btn-secondary" type="button" onclick="document.getElementById('editar-cuenta-{{ $cuenta->id }}').classList.toggle('oculto')">Editar</button>
                            <form action="{{ route('configuracion.cuentas.destroy', $cuenta) }}" method="POST" onsubmit="return confirm('¿Eliminar esta cuenta bancaria?');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <tr id="editar-cuenta-{{ $cuenta->id }}" class="oculto">
                        <td colspan="5">
                            <form action="{{ route('configuracion.cuentas.update', $cuenta) }}" method="POST" style="margin:0;">
                                @csrf
                                @method('PUT')
                                <div class="grid-4">
                                    <div>
                                        <label>Banco</label>
                                        <input type="text" name="banco" value="{{ $cuenta->banco }}" required>
                                    </div>
                                    <div>
                                        <label>Tipo de cuenta</label>
                                        <input type="text" name="tipo_cuenta" value="{{ $cuenta->tipo_cuenta }}" required>
                                    </div>
                                    <div>
                                        <label>Numero de cuenta</label>
                                        <input type="text" name="numero_cuenta" value="{{ $cuenta->numero_cuenta }}" required>
                                    </div>
                                    <div>
                                        <label>Titular</label>
                                        <input type="text" name="titular" value="{{ $cuenta->titular }}" required>
                                    </div>
                                </div>
                                <button class="btn btn-sm" type="submit">Guardar cambios</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">No hay cuentas bancarias registradas.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3>Agregar cuenta bancaria</h3>
        <form action="{{ route('configuracion.cuentas.store') }}" method="POST">
            @csrf
            <div class="grid-4">
                <div>
                    <label>Banco</label>
                    <input type="text" name="banco" required>
                </div>
                <div>
                    <label>Tipo de cuenta</label>
                    <input type="text" name="tipo_cuenta" placeholder="Ahorros / Corriente" required>
                </div>
                <div>
                    <label>Numero de cuenta</label>
                    <input type="text" name="numero_cuenta" required>
                </div>
                <div>
                    <label>Titular</label>
                    <input type="text" name="titular" required>
                </div>
            </div>
            <button class="btn" type="submit">Agregar cuenta</button>
        </form>
    </div>

    <style>.oculto { display: none; }</style>
@endsection
