@extends('layouts.app')

@section('title', 'Nuevo torneo')

@section('content')
    <x-breadcrumbs :items="['Torneos' => route('torneos.index'), 'Nuevo torneo' => null]" />
    <div class="card">
        <h1>Nuevo torneo</h1>
        <form action="{{ route('torneos.store') }}" method="POST">
            @csrf
            @include('torneos._form', ['torneo' => null])
            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('torneos.index') }}">Cancelar</a>
        </form>
    </div>
@endsection
