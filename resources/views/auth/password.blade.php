@extends('layouts.app')

@section('title', 'Cambiar contraseña')

@section('content')
    <x-breadcrumbs :items="['Cambiar contraseña' => null]" />
    <div class="card" style="max-width: 420px;">
        <h1>Cambiar contraseña</h1>
        <form action="{{ route('password.update') }}" method="POST">
            @csrf
            @method('PUT')
            <label>Contrasena actual</label>
            <input type="password" name="password_actual" required>

            <label>Nueva contrasena</label>
            <input type="password" name="password" required minlength="8">

            <label>Confirmar nueva contrasena</label>
            <input type="password" name="password_confirmation" required minlength="8">

            <button class="btn" type="submit">Actualizar contrasena</button>
        </form>
    </div>
@endsection
