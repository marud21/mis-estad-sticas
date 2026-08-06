@extends('layouts.app')

@section('title', 'Nuevo tipo de cargo')

@section('content')
    <x-breadcrumbs :items="['Tipos de cargo' => route('tipos-cargo.index'), 'Nuevo tipo de cargo' => null]" />
    <div class="card">
        <h1>Nuevo tipo de cargo</h1>
        <form action="{{ route('tipos-cargo.store') }}" method="POST">
            @csrf
            @include('tipos-cargo._form', ['tipoCargo' => null])
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('tipos-cargo.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
