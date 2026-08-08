@extends('layouts.app')

@section('title', 'Cierre de caja')

@section('content')
    <x-breadcrumbs :items="['Reportes' => route('reportes-contables.index'), 'Cierre de caja' => null]" />

    <div class="card">
        <div class="card-header">
            <h1 style="margin:0;">Cierre de caja</h1>
            <a class="btn" href="{{ route('cierre-caja.create') }}">Nuevo cierre</a>
        </div>
        <p style="color:#666; font-size:14px;">Historial de cierres de caja guardados, uno por dia.</p>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="text-right">Efectivo</th>
                        <th class="text-right">Transferencia</th>
                        <th class="text-right">Total ingresos</th>
                        <th class="text-right">Gastos</th>
                        <th class="text-right">Neto efectivo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cierres as $cierre)
                        <tr>
                            <td>{{ $cierre->fecha->format('d/m/Y') }}</td>
                            <td class="text-right">${{ number_format($cierre->total_efectivo, 0, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($cierre->total_transferencia, 0, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($cierre->total_ingresos, 0, ',', '.') }}</td>
                            <td class="text-right">${{ number_format($cierre->total_gastos, 0, ',', '.') }}</td>
                            <td class="text-right"><strong>${{ number_format($cierre->total_neto_efectivo, 0, ',', '.') }}</strong></td>
                            <td class="actions">
                                <a class="btn btn-sm btn-secondary" href="{{ route('cierre-caja.show', $cierre) }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Aun no hay cierres de caja guardados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $cierres->links() }}
    </div>
@endsection
