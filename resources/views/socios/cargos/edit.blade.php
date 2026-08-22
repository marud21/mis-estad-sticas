@extends('layouts.app')

@section('title', 'Editar cargo')

@section('content')
    <x-breadcrumbs :items="['Socios' => route('socios.index'), $socio->nombre_completo => route('socios.show', $socio), 'Editar cargo' => null]" />
    <div class="card">
        <h1>Editar cargo de {{ $socio->nombre_completo }}</h1>
        <form action="{{ route('socios.cargos.update', [$socio, $cargo]) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="grid-2">
                <div>
                    <label>Tipo de cargo</label>
                    <select name="tipo_cargo_id" required>
                        @foreach ($tiposCargo as $tipo)
                            <option value="{{ $tipo->id }}" @selected(old('tipo_cargo_id', $cargo->tipo_cargo_id) == $tipo->id)>{{ $tipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Monto</label>
                    <input type="number" step="0.01" name="monto" value="{{ old('monto', $cargo->monto) }}" required>
                </div>
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', $cargo->fecha->format('Y-m-d')) }}" required>
                </div>
                <div>
                    <label>Equipo (opcional)</label>
                    <select name="equipo_id">
                        <option value="">-- General, sin equipo --</option>
                        @foreach ($socio->equipos as $equipo)
                            <option value="{{ $equipo->id }}" @selected(old('equipo_id', $cargo->equipo_id) == $equipo->id)>{{ $equipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label>Descripcion</label>
                    <input type="text" name="descripcion" value="{{ old('descripcion', $cargo->descripcion) }}">
                </div>
            </div>
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('socios.show', $socio) }}">Cancelar</a>
        </form>
    </div>
@endsection
