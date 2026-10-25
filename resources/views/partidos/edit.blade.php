@extends('layouts.app')

@section('title', 'Editar partido')

@section('content')
    <x-breadcrumbs :items="['Partidos' => route('partidos.index'), 'Editar partido' => null]" />
    <div class="card">
        <h1>Editar partido</h1>
        <p style="color:#666; font-size:13px; margin-top:-8px;">
            {{ $partido->equipoLocal->nombre ?? '-' }} vs {{ $partido->equipoVisitante->nombre ?? '-' }}
            &middot; {{ $partido->fecha_hora->format('d/m/Y H:i') }}
        </p>

        <form action="{{ route('partidos.update', $partido) }}" method="POST">
            @csrf
            @method('PUT')
            @include('partidos._form', ['partido' => $partido, 'torneo' => $partido->torneo])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('partidos.index', ['torneo_id' => $partido->torneo_id]) }}">Cancelar</a>
        </form>
    </div>
@endsection
