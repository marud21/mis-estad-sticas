@extends('layouts.app')

@section('title', 'Nuevo socio')

@section('content')
    <x-breadcrumbs :items="['Socios' => route('socios.index'), 'Nuevo socio' => null]" />
    <div class="card">
        <h1>Nuevo socio</h1>
        <form action="{{ route('socios.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @include('socios._form', ['socio' => null])

            <h3>Cargos iniciales</h3>
            <p style="font-size:13px; color:#666; margin-top:-8px;">Selecciona todos los cargos que apliquen. Los montos se pueden ajustar.</p>
            <table>
                <thead>
                    <tr><th></th><th>Cargo</th><th>Monto</th><th>Fecha</th></tr>
                </thead>
                <tbody>
                    @foreach ($tiposCargo as $i => $tipo)
                        <tr>
                            <td style="width:30px;">
                                <input type="checkbox" class="cargo-check" data-index="{{ $i }}" style="width:auto; margin:0;">
                            </td>
                            <td>{{ $tipo->nombre }}</td>
                            <td style="width:160px;">
                                <input type="hidden" name="cargos[{{ $i }}][tipo_cargo_id]" class="cargo-tipo-input" data-index="{{ $i }}" disabled value="{{ $tipo->id }}">
                                <input type="number" step="0.01" name="cargos[{{ $i }}][monto]" class="cargo-monto-input" data-index="{{ $i }}" disabled value="{{ $tipo->monto_default }}" style="margin-bottom:0;">
                            </td>
                            <td style="width:170px;">
                                <input type="date" name="cargos[{{ $i }}][fecha]" class="cargo-fecha-input" data-index="{{ $i }}" disabled value="{{ date('Y-m-d') }}" style="margin-bottom:0;">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <button class="btn" type="submit">Guardar</button>
            <a class="btn btn-secondary" href="{{ route('socios.index') }}">Cancelar</a>
        </form>
    </div>

    <script>
        document.querySelectorAll('.cargo-check').forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                var index = this.dataset.index;
                document.querySelectorAll('[data-index="' + index + '"]').forEach(function (input) {
                    if (input === checkbox) return;
                    input.disabled = !checkbox.checked;
                });
            });
        });
    </script>
@endsection
