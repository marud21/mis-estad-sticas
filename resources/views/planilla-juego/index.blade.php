@extends('layouts.app')

@section('title', 'Planilla de juego')

@section('content')
    <x-breadcrumbs :items="['Planilla de juego' => null]" />

    <div class="card" style="max-width: 640px;">
        <h1>Generar planilla de juego</h1>
        <p style="font-size:13px; color:#666; margin-top:-8px;">
            Elige los dos equipos que se van a enfrentar. Se incluyen los jugadores activos de cada equipo,
            ordenados por numero de camiseta. Las columnas de amonestacion, roja, goles y resultado quedan
            en blanco para llenar a mano durante el partido.
        </p>

        <form action="{{ route('planilla-juego.generar') }}" method="POST">
            @csrf
            <div class="grid-2">
                <div>
                    <label>Equipo local</label>
                    <select name="equipo_local_id" required>
                        <option value="">-- Selecciona --</option>
                        @foreach ($equipos as $equipo)
                            <option value="{{ $equipo->id }}" @selected(old('equipo_local_id') == $equipo->id)>{{ $equipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Equipo visitante</label>
                    <select name="equipo_visitante_id" required>
                        <option value="">-- Selecciona --</option>
                        @foreach ($equipos as $equipo)
                            <option value="{{ $equipo->id }}" @selected(old('equipo_visitante_id') == $equipo->id)>{{ $equipo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Torneo</label>
                    <input type="text" name="torneo" value="{{ old('torneo') }}">
                </div>
                <div>
                    <label>Jornada</label>
                    <input type="text" name="jornada" value="{{ old('jornada') }}">
                </div>
                <div>
                    <label>Cancha</label>
                    <input type="text" name="cancha" value="{{ old('cancha') }}">
                </div>
                <div>
                    <label>Fecha</label>
                    <input type="date" name="fecha" value="{{ old('fecha', date('Y-m-d')) }}">
                </div>
                <div>
                    <label>Hora</label>
                    <input type="time" name="hora" value="{{ old('hora') }}">
                </div>
                <div>
                    <label>Arbitro</label>
                    <input type="text" name="arbitro" value="{{ old('arbitro') }}">
                </div>
            </div>
            <button class="btn" type="submit">Generar PDF</button>
        </form>
    </div>
@endsection
