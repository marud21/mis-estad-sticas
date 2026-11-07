{{--
    Las figuras de la ultima jornada: el jugador del partido que eligio
    quien registro cada resultado.

    Va separado por categoria, como el resto de la portada, y cada una
    trae su propia jornada. Las categorias donde no se eligio a nadie no
    aparecen, y si no hay ninguna la seccion entera se omite.
--}}
@if ($mejoresPorCategoria->isNotEmpty())
    <div class="card">
        <h2>&#11088; Mejores de la jornada</h2>

        @foreach ($mejoresPorCategoria as $categoria => $datos)
            <h3>{{ $categoria }}</h3>
            <p class="sub">{{ $datos['jornada'] }} &middot; el jugador destacado de cada partido.</p>

            <ul class="figuras">
                @foreach ($datos['figuras'] as $figura)
                    <li class="figura-fila">
                        @if ($figura->foto)
                            <img class="figura-foto"
                                 src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($figura->foto) }}"
                                 alt="Foto de {{ $figura->jugador }}">
                        @else
                            <span class="figura-foto figura-iniciales" aria-hidden="true">
                                {{ \Illuminate\Support\Str::of($figura->jugador)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                            </span>
                        @endif

                        <div class="figura-datos">
                            <strong>{{ $figura->jugador }}</strong>
                            <span>{{ $figura->equipo }}</span>
                        </div>

                        <span class="figura-partido">{{ $figura->partido }}</span>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </div>
@endif
