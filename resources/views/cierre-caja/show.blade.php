@extends('layouts.app')

@section('title', 'Cierre de caja - ' . $cierre->fecha->format('d/m/Y'))

@section('content')
    <x-breadcrumbs :items="['Reportes' => route('reportes-contables.index'), 'Cierre de caja' => route('cierre-caja.index'), $cierre->fecha->format('d/m/Y') => null]" />

    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">
                Cierre de caja &mdash; {{ $cierre->fecha->format('d/m/Y') }}
                @if ($cierre->anulado)
                    <span class="badge badge-retirado" style="margin-left:6px;">Anulado</span>
                @endif
            </h1>
            <a class="btn btn-secondary" href="{{ route('cierre-caja.index') }}">Volver</a>
        </div>

        @if ($cierre->anulado)
            <div class="alert alert-error" style="margin-top:16px;">
                <strong>Este cierre fue anulado</strong> por {{ $cierre->anuladoPor->name ?? 'un usuario' }} el {{ $cierre->anulado_en->format('d/m/Y H:i') }}.<br>
                Motivo: {{ $cierre->anulado_motivo }}
                <br><br>
                Para registrar el cierre correcto de este dia, ve a
                <a href="{{ route('cierre-caja.create', ['fecha' => $cierre->fecha->format('Y-m-d')]) }}">Nuevo cierre</a>.
            </div>
        @endif

        <div class="grid-2" style="margin-top:16px; {{ $cierre->anulado ? 'opacity:0.6;' : '' }}">
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

        @unless ($cierre->anulado)
            <hr style="border:none; border-top:1px solid var(--gris-borde); margin:20px 0;">
            <h3 style="color:#b3261e;">Anular este cierre</h3>
            <p style="font-size:13px; color:#666; margin-top:-8px;">
                Usa esto si el cierre se guardo por error o antes de tiempo. El cierre queda marcado como anulado
                (no se borra, para dejar historial) y podras crear el cierre correcto para esta misma fecha desde "Nuevo cierre".
            </p>
            <form action="{{ route('cierre-caja.anular', $cierre) }}" method="POST" style="max-width:500px;"
                  onsubmit="return confirm('¿Anular el cierre del {{ $cierre->fecha->format('d/m/Y') }}? Esta accion no se puede deshacer, pero podras crear uno nuevo para la misma fecha.');">
                @csrf
                @method('PATCH')
                <label>Motivo de la anulacion</label>
                <textarea name="motivo" rows="2" maxlength="500" required placeholder="Ej: se cerro antes de registrar un pago"></textarea>
                @error('motivo')
                    <p style="color:#b3261e; font-size:12px; margin-top:-8px;">{{ $message }}</p>
                @enderror
                <button class="btn btn-danger" type="submit">Anular cierre</button>
            </form>
        @endunless
    </div>
@endsection
