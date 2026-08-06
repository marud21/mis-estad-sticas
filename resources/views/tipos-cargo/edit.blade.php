@extends('layouts.app')

@section('title', 'Editar tipo de cargo')

@section('content')
    <x-breadcrumbs :items="['Tipos de cargo' => route('tipos-cargo.index'), 'Editar' => null]" />
    <div class="card">
        <h1>Editar tipo de cargo</h1>
        <form action="{{ route('tipos-cargo.update', $tipoCargo) }}" method="POST">
            @csrf
            @method('PUT')
            @include('tipos-cargo._form', ['tipoCargo' => $tipoCargo])
            <button class="btn" type="submit">Guardar cambios</button>
            <a class="btn btn-secondary" href="{{ route('tipos-cargo.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
