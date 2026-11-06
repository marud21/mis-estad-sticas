{{--
    Saludo a quien cumple años hoy.

    Va arriba a la derecha, al lado del encabezado del torneo. Si hoy no
    cumple nadie no se dibuja nada y el encabezado ocupa todo el ancho.

    Cuando cumple mas de uno se muestran de a uno, rotando solos. El HTML
    los trae a todos y es el guion el que convierte la lista en carrusel:
    asi, si el guion no corre, se ven todos uno debajo de otro en vez de
    quedar escondidos.
--}}
@if ($cumpleaneros->isNotEmpty())
    @php($varios = $cumpleaneros->count() > 1)

    <aside class="card cumple" @if ($varios) data-carrusel @endif>
        <h2 class="cumple-titulo">&#127874; Feliz Cumpleaños</h2>

        <ul class="cumple-lista">
            @foreach ($cumpleaneros as $socio)
                <li class="cumple-socio">
                    @if ($socio->foto_path)
                        <img class="cumple-foto"
                             src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($socio->foto_path) }}"
                             alt="Foto de {{ $socio->nombre_completo }}">
                    @else
                        {{-- Sin foto se muestran las iniciales, para que la
                             fila no quede coja. --}}
                        <span class="cumple-foto cumple-iniciales" aria-hidden="true">
                            {{ \Illuminate\Support\Str::of($socio->nombre_completo)->explode(' ')->filter()->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                        </span>
                    @endif

                    <div class="cumple-datos">
                        <strong>{{ $socio->nombre_completo }}</strong>
                        <span>{{ $socio->equipos->pluck('nombre')->implode(', ') ?: 'Sin equipo' }}</span>
                    </div>
                </li>
            @endforeach
        </ul>

        @if ($varios)
            {{-- Los puntos quedan ocultos mientras el carrusel no arranque,
                 para no mostrar controles que no hacen nada. --}}
            <div class="cumple-puntos" role="tablist" aria-label="Cumpleañeros de hoy">
                @foreach ($cumpleaneros as $i => $socio)
                    <button type="button" class="cumple-punto" role="tab"
                            data-ir="{{ $i }}"
                            aria-label="Ver a {{ $socio->nombre_completo }}"></button>
                @endforeach
            </div>
        @endif

        <p class="cumple-mensaje">
            @if ($varios)
                Que lo disfruten. De parte de toda la corporación.
            @else
                Que lo disfrutes. De parte de toda la corporación.
            @endif
        </p>
    </aside>

    @if ($varios)
        <script>
        (function () {
            var caja = document.querySelector('.cumple[data-carrusel]');
            if (!caja) {
                return;
            }

            var lista = caja.querySelector('.cumple-lista');
            var fichas = Array.prototype.slice.call(caja.querySelectorAll('.cumple-socio'));
            var puntos = Array.prototype.slice.call(caja.querySelectorAll('.cumple-punto'));
            if (fichas.length < 2) {
                return;
            }

            // Recien aqui se convierte en carrusel: antes de esta linea la
            // lista se ve completa, que es lo que pasa si el guion falla.
            lista.classList.add('carrusel');

            var actual = 0;
            var reloj = null;
            var ESPERA = 5000;

            function mostrar(i) {
                actual = (i + fichas.length) % fichas.length;

                fichas.forEach(function (f, n) {
                    f.classList.toggle('activo', n === actual);
                });
                puntos.forEach(function (p, n) {
                    p.classList.toggle('activo', n === actual);
                    p.setAttribute('aria-selected', n === actual ? 'true' : 'false');
                });
            }

            function arrancar() {
                detener();
                reloj = setInterval(function () { mostrar(actual + 1); }, ESPERA);
            }

            function detener() {
                if (reloj) {
                    clearInterval(reloj);
                    reloj = null;
                }
            }

            puntos.forEach(function (p) {
                p.addEventListener('click', function () {
                    mostrar(parseInt(p.dataset.ir, 10));
                    arrancar();
                });
            });

            // Mientras alguien esta mirando o navegando con el teclado no se
            // le cambia la ficha debajo de los ojos.
            caja.addEventListener('mouseenter', detener);
            caja.addEventListener('mouseleave', arrancar);
            caja.addEventListener('focusin', detener);
            caja.addEventListener('focusout', arrancar);

            // Si la pestaña no se ve, no tiene sentido seguir rotando.
            document.addEventListener('visibilitychange', function () {
                document.hidden ? detener() : arrancar();
            });

            mostrar(0);
            arrancar();
        })();
        </script>
    @endif
@endif
