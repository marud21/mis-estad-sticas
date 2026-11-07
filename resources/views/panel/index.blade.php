@extends('layouts.app')

@section('title', 'Panel')

@php
    $plata = fn ($v) => '$'.number_format((float) $v, 0, ',', '.');
@endphp

@section('content')
    <x-breadcrumbs :items="['Panel' => null]" />

    @include('panel._filtros')

    {{-- ---------- Las cuatro cifras que resumen todo ---------- --}}
    <div class="cifras">
        <div class="cifra">
            <span class="cifra-rotulo">Socios activos</span>
            <strong class="cifra-valor">{{ number_format($socios['por_estado']->firstWhere('valor', \App\Models\Socio::ESTADO_ACTIVO)['total'] ?? 0, 0, ',', '.') }}</strong>
            <span class="cifra-nota">de {{ number_format($socios['total'], 0, ',', '.') }} registrados</span>
        </div>
        <div class="cifra cifra-alerta">
            <span class="cifra-rotulo">Deuda acumulada</span>
            <strong class="cifra-valor">{{ $plata($finanzas['deuda_total']) }}</strong>
            <span class="cifra-nota">historica, no solo del periodo</span>
        </div>
        <div class="cifra cifra-bien">
            <span class="cifra-rotulo">Recaudado en el periodo</span>
            <strong class="cifra-valor">{{ $plata($finanzas['recaudado_rango']) }}</strong>
            <span class="cifra-nota">{{ number_format($finanzas['pagos_rango'], 0, ',', '.') }} pagos</span>
        </div>
        <div class="cifra">
            <span class="cifra-rotulo">Partidos jugados</span>
            <strong class="cifra-valor">{{ number_format($deportivo['jugados'], 0, ',', '.') }}</strong>
            <span class="cifra-nota">{{ $deportivo['goles'] }} goles &middot; {{ $deportivo['programados'] }} por jugar</span>
        </div>
    </div>

    @include('panel._finanzas')
    @include('panel._socios')
    @include('panel._deportivo')
    @include('panel._salud')

    @include('panel._estilos')
@endsection
