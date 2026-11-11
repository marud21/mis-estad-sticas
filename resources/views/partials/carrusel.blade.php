{{--
    Carrusel de la portada.

    Si no hay imagenes publicadas no se dibuja nada. Con una sola se
    muestra fija, sin controles: no hay nada que rotar.

    Igual que el de cumpleaños, el HTML trae todas las imagenes y es el
    guion el que arma el carrusel. Si el guion no corre se ven una debajo
    de otra, que es feo pero se ve: nadie se queda sin verlas.
--}}
@if ($carrusel->isNotEmpty())
    @php($varias = $carrusel->count() > 1)

    <section class="carrusel" @if ($varias) data-carrusel-portada @endif aria-label="Imagenes destacadas">
        <ul class="carrusel-lista">
            @foreach ($carrusel as $i => $imagen)
                <li class="carrusel-item">
                    @if ($imagen->enlace)
                        <a href="{{ $imagen->enlace }}" target="_blank" rel="noopener noreferrer">
                    @endif

                    <img src="{{ $imagen->url }}"
                         alt="{{ $imagen->titulo ?? 'Imagen destacada' }}"
                         {{-- La primera se carga de inmediato porque se ve al
                              entrar; las demas solo cuando hagan falta. --}}
                         loading="{{ $i === 0 ? 'eager' : 'lazy' }}">

                    @if ($imagen->titulo)
                        <span class="carrusel-pie">{{ $imagen->titulo }}</span>
                    @endif

                    @if ($imagen->enlace)
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if ($varias)
            <div class="carrusel-puntos">
                @foreach ($carrusel as $i => $imagen)
                    <button type="button" class="carrusel-punto" data-ir="{{ $i }}"
                            aria-label="Ver imagen {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </section>

    <style>
        .carrusel { position: relative; margin-bottom: 18px; }
        .carrusel-lista { list-style: none; margin: 0; padding: 0; }
        .carrusel-item { line-height: 0; }
        .carrusel-item a { display: block; position: relative; }
        .carrusel img {
            width: 100%;
            aspect-ratio: 8 / 3;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--gris-borde);
            background: var(--gris-claro);
            display: block;
        }
        .carrusel-pie {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            padding: 14px 16px;
            background: linear-gradient(to top, rgba(11, 37, 69, .78), transparent);
            color: var(--blanco);
            font-size: 15px;
            line-height: 1.3;
            border-radius: 0 0 8px 8px;
        }
        .carrusel-item { position: relative; }

        /* Solo se vuelve carrusel cuando el guion arranca. */
        .carrusel-lista.rotando > .carrusel-item { display: none; }
        .carrusel-lista.rotando > .carrusel-item.activo {
            display: block;
            animation: carrusel-aparecer .4s ease;
        }
        @keyframes carrusel-aparecer {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .carrusel-puntos { display: none; gap: 7px; justify-content: center; margin-top: 10px; }
        .carrusel-lista.rotando ~ .carrusel-puntos { display: flex; }
        .carrusel-punto {
            width: 9px; height: 9px; padding: 0;
            border: 0; border-radius: 50%;
            background: var(--gris-borde);
            cursor: pointer;
            transition: background .2s;
        }
        .carrusel-punto.activo { background: var(--azul-claro); }

        @media (prefers-reduced-motion: reduce) {
            .carrusel-lista.rotando > .carrusel-item.activo { animation: none; }
        }

        @media (max-width: 560px) {
            .carrusel img { aspect-ratio: 3 / 2; }
            .carrusel-pie { font-size: 13px; padding: 10px 12px; }
        }
    </style>

    @if ($varias)
        <script>
        (function () {
            var caja = document.querySelector('.carrusel[data-carrusel-portada]');
            if (!caja) {
                return;
            }

            var lista = caja.querySelector('.carrusel-lista');
            var items = Array.prototype.slice.call(caja.querySelectorAll('.carrusel-item'));
            var puntos = Array.prototype.slice.call(caja.querySelectorAll('.carrusel-punto'));
            if (items.length < 2) {
                return;
            }

            lista.classList.add('rotando');

            var actual = 0;
            var reloj = null;
            var ESPERA = 6000;

            function mostrar(i) {
                actual = (i + items.length) % items.length;
                items.forEach(function (x, n) { x.classList.toggle('activo', n === actual); });
                puntos.forEach(function (p, n) {
                    p.classList.toggle('activo', n === actual);
                    p.setAttribute('aria-selected', n === actual ? 'true' : 'false');
                });
            }

            function arrancar() { detener(); reloj = setInterval(function () { mostrar(actual + 1); }, ESPERA); }
            function detener() { if (reloj) { clearInterval(reloj); reloj = null; } }

            puntos.forEach(function (p) {
                p.addEventListener('click', function () {
                    mostrar(parseInt(p.dataset.ir, 10));
                    arrancar();
                });
            });

            caja.addEventListener('mouseenter', detener);
            caja.addEventListener('mouseleave', arrancar);
            caja.addEventListener('focusin', detener);
            caja.addEventListener('focusout', arrancar);
            document.addEventListener('visibilitychange', function () {
                document.hidden ? detener() : arrancar();
            });

            mostrar(0);
            arrancar();
        })();
        </script>
    @endif
@endif
