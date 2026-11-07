{{--
    Huecos en la informacion cargada. No son fallas del sistema: son
    cosas que conviene revisar y que de otro modo no se notan hasta que
    algo no cuadra. Si no hay ninguna, la seccion no se dibuja.
--}}
@if ($salud->isNotEmpty())
    <div class="card">
        <h2>&#128269; Para revisar</h2>
        <p class="panel-sub">Datos incompletos o raros que vale la pena mirar. No afectan el funcionamiento.</p>

        <div class="table-scroll">
            <table class="panel-tabla">
                <thead>
                    <tr><th>Asunto</th><th class="n">Cuantos</th><th>Por que importa</th></tr>
                </thead>
                <tbody>
                    @foreach ($salud as $fila)
                        <tr>
                            <td>
                                @if ($fila['enlace'])
                                    <a href="{{ $fila['enlace'] }}">{{ $fila['asunto'] }}</a>
                                @else
                                    {{ $fila['asunto'] }}
                                @endif
                            </td>
                            <td class="n">{{ number_format($fila['total'], 0, ',', '.') }}</td>
                            <td class="panel-porque">{{ $fila['porque'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
