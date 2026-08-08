@extends('layouts.app')

@section('title', 'Cierre de caja - ' . $cierre->fecha->format('d/m/Y'))

@section('content')
    <x-breadcrumbs :items="['Reportes' => route('reportes-contables.index'), 'Cierre de caja' => route('cierre-caja.index'), $cierre->fecha->format('d/m/Y') => null]" />

    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">Cierre de caja &mdash; {{ $cierre->fecha->format('d/m/Y') }}</h1>
            <a class="btn btn-secondary" href="{{ route('cierre-caja.index') }}">Volver</a>
        </div>

        <div class="grid-2" style="margin-top:16px;">
            <div>
                <p><strong>Ingresos en efectivo:</strong> ${{ number_format($cierre->total_efectivo, 0, ',', '.') }}</p>
                <p><strong>Ingresos en transferencia:</strong> ${{ number_format($cierre->total_transferencia, 0, ',', '.') }}</p>
                <p><strong>Total de ingresos del dia:</strong> ${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</p>
            </div>
            <div>
                <p><strong>Total de gastos:</strong> ${{ number_format($cierre->total_gastos, 0, ',', '.') }}</p>
                <p><strong>Total neto en efectivo:</strong> <span class="{{ $cierre->total_neto_efectivo < 0 ? 'deuda-positiva' : 'deuda-cero' }}">${{ number_format($cierre->total_neto_efectivo, 0, ',', '.') }}</span></p>
                <p><strong>Guardado por:</strong> {{ $cierre->usuario->name ?? 'Sistema' }} el {{ $cierre->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        @if ($cierre->notas)
            <p><strong>Notas:</strong> {{ $cierre->notas }}</p>
        @endif

        <h3>Gastos del dia</h3>
        <table>
            <thead>
                <tr><th>Descripcion</th><th class="text-right">Monto</th></tr>
            </thead>
            <tbody>
                @forelse ($cierre->gastos as $gasto)
                    <tr>
                        <td>{{ $gasto->descripcion }}</td>
                        <td class="text-right">${{ number_format($gasto->monto, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">No se registraron gastos este dia.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
