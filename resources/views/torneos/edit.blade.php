@extends('layouts.app')

@section('title', 'Editar torneo')

@section('content')
    <x-breadcrumbs :items="['Torneos' => route('torneos.index'), $torneo->nombre => route('torneos.show', $torneo), 'Editar' => null]" />
    <div class="card">
        <h1>Editar torneo</h1>
        <form action="{{ route('torneos.update', $torneo) }}" method="POST">
            @csrf
            @method('PUT')
            @include('torneos._form', ['torneo' => $torneo])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('torneos.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
