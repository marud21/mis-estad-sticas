{{--
    Aviso de que una tabla ancha se desliza de lado.

    En el telefono las tablas con muchas columnas (la de posiciones, sobre
    todo) no caben y hay que correrlas, pero el navegador esconde la barra
    de desplazamiento hasta que uno la toca, asi que no se nota que falta
    contenido a la derecha. Aqui se marca de tres formas a la vez:
    una barra siempre visible, un degradado en el borde derecho y un
    renglon que lo dice con palabras.

    Ojo con cual es el elemento que se desplaza. En la portada es el
    contenedor .tabla-scroll, pero en el panel, en pantallas angostas, hay
    una regla que vuelve a la propia <table> un bloque con overflow, asi
    que ahi la que se mueve es la tabla. Por eso no se marca por clase
    sino por lo unico que importa: que el elemento de verdad tenga
    contenido escondido. Asi nunca se pinta una barra que no sirva.
--}}
<style>
    /* La barra solo se fuerza donde hay algo que desplazar, para no
       dejar una barra decorativa que no haga nada. */
    .desplazable {
        scrollbar-width: thin;
        scrollbar-color: var(--azul-claro) var(--gris-borde);
    }
    .desplazable::-webkit-scrollbar {
        -webkit-appearance: none;
        height: 8px;
    }
    .desplazable::-webkit-scrollbar-track {
        background: var(--gris-borde);
        border-radius: 4px;
    }
    .desplazable::-webkit-scrollbar-thumb {
        background: var(--azul-claro);
        border-radius: 4px;
    }

    /* La envoltura no se desplaza, asi el degradado queda fijo al borde. */
    .con-desliz { position: relative; }
    .con-desliz::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 10px;
        width: 30px;
        pointer-events: none;
        opacity: 0;
        transition: opacity .2s ease;
        background: linear-gradient(to left, rgba(11, 37, 69, .18), rgba(11, 37, 69, 0));
    }
    .con-desliz.desborda::after { opacity: 1; }
    .con-desliz.desborda.al-final::after { opacity: 0; }

    .aviso-desliz {
        display: none;
        margin: 8px 0 0;
        font-size: 12px;
        color: var(--azul-claro);
        white-space: normal;
    }
    .con-desliz.desborda .aviso-desliz { display: block; }
    .con-desliz.desborda.al-final .aviso-desliz { display: none; }

    .aviso-desliz .flecha {
        display: inline-block;
        animation: deslizar 1.4s ease-in-out infinite;
    }
    @keyframes deslizar {
        0%, 100% { transform: translateX(0); }
        50% { transform: translateX(4px); }
    }

    /* Si alguien pidio menos movimiento en su sistema, la flecha se queda
       quieta: el aviso se entiende igual. */
    @media (prefers-reduced-motion: reduce) {
        .aviso-desliz .flecha { animation: none; }
    }
</style>

<script>
    (function () {
        function sePuedeDesplazar(el) {
            var ov = window.getComputedStyle(el).overflowX;

            return ov === 'auto' || ov === 'scroll';
        }

        function preparar(el) {
            if (el.dataset.desliz) {
                return;
            }
            el.dataset.desliz = '1';

            var envoltura = document.createElement('div');
            envoltura.className = 'con-desliz';
            el.parentNode.insertBefore(envoltura, el);
            envoltura.appendChild(el);

            var aviso = document.createElement('p');
            aviso.className = 'aviso-desliz';
            aviso.innerHTML = 'Desliza la tabla para ver todas las columnas <span class="flecha">&#8594;</span>';
            envoltura.appendChild(aviso);

            function revisar() {
                var sobra = el.scrollWidth - el.clientWidth;
                var hayMas = sobra > 2;

                el.classList.toggle('desplazable', hayMas);
                envoltura.classList.toggle('desborda', hayMas);
                envoltura.classList.toggle('al-final', el.scrollLeft >= sobra - 2);
            }

            el.addEventListener('scroll', revisar, { passive: true });
            window.addEventListener('resize', revisar);

            // Al desplegar filas con "Ver mas" puede aparecer la barra
            // vertical y cambiar el ancho util, asi que se vuelve a medir.
            if (window.ResizeObserver) {
                new ResizeObserver(revisar).observe(el);
            }

            revisar();
        }

        document.addEventListener('DOMContentLoaded', function () {
            var candidatos = document.querySelectorAll('.tabla-scroll, .table-scroll, table');

            Array.prototype.forEach.call(candidatos, function (el) {
                if (sePuedeDesplazar(el)) {
                    preparar(el);
                }
            });
        });
    })();
</script>
