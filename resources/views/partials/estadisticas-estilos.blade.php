{{-- Estilos de las tablas de estadisticas de la portada publica. --}}
<style>
.tabla-scroll { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th {
            background: var(--azul-oscuro); color: var(--blanco);
            text-align: left; padding: 8px 10px; font-size: 12px;
            text-transform: uppercase; letter-spacing: .3px; white-space: nowrap;
        }
        td { padding: 8px 10px; border-bottom: 1px solid var(--gris-borde); }
        tr:last-child td { border-bottom: 0; }
        .c { text-align: center; }
        .destacado { font-weight: 700; color: var(--azul-oscuro); }
        .puesto {
            display: inline-block; min-width: 22px; text-align: center;
            font-weight: 700; color: var(--azul-claro);
        }
        .lider td { background: #eaf4ea; }

        .marcador { font-weight: 700; white-space: nowrap; }
        .amarilla, .roja { font-weight: 700; }
        .amarilla { color: var(--amarillo); }
        .roja { color: var(--rojo); }
        .vacio { color: #999; font-size: 14px; padding: 6px 0; }

        /* Marca de walkover junto al marcador. */
        .wo {
            display: inline-block;
            margin-left: 4px;
            padding: 1px 5px;
            border-radius: 4px;
            background: var(--rojo);
            color: var(--blanco);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .3px;
            vertical-align: middle;
        }

        .rejilla { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        /* Sin esto los elementos del grid no se encogen por debajo de su
           contenido y las tablas empujan la pagina a lo ancho en telefonos. */
        .rejilla > * { min-width: 0; }

        /* Filas que se despliegan con el boton "Ver mas". */
        tr.oculto { display: none; }
        .ver-mas {
            margin-top: 10px;
            background: none;
            border: 1px solid var(--azul-claro);
            color: var(--azul-claro);
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-family: inherit;
            cursor: pointer;
        }
        .ver-mas:hover { background: var(--azul-claro); color: var(--blanco); }

        /* Mejores de la jornada: listado con la foto de cada figura. */
        .figuras { list-style: none; margin: 10px 0 0; padding: 0; display: grid; gap: 10px; }
        /* Aire entre una categoria y el encabezado de la siguiente. */
        .figuras:not(:last-child) { margin-bottom: 18px; }
        .figura-fila {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            border-left: 3px solid var(--amarillo);
        }
        .figura-foto {
            width: 46px; height: 46px; flex: none;
            object-fit: cover; border-radius: 50%;
            border: 2px solid var(--amarillo);
        }
        .figura-iniciales {
            display: flex; align-items: center; justify-content: center;
            background: var(--gris-claro); color: var(--azul-oscuro);
            font-weight: 700; font-size: 15px;
        }
        .figura-datos { display: flex; flex-direction: column; min-width: 0; }
        .figura-datos strong { color: var(--azul-oscuro); font-size: 14px; line-height: 1.3; }
        .figura-datos span { color: #666; font-size: 12px; }
        /* El partido se va a la derecha y, si no cabe, baja de renglon. */
        .figura-partido { margin-left: auto; color: #888; font-size: 12px; text-align: right; }

        @media (max-width: 560px) {
            .figura-partido { margin-left: 58px; text-align: left; width: 100%; }
            .figura-fila { flex-wrap: wrap; }
        }

        /* En telefonos dos tablas lado a lado quedan ilegibles: una debajo de otra. */
        @media (max-width: 700px) {
            .rejilla { grid-template-columns: 1fr; }
        }
</style>

<script>
    /**
     * Las listas largas vienen completas en el HTML con las filas de mas
     * ocultas: el boton solo las muestra, asi no hace falta recargar ni
     * volver a consultar el servidor.
     */
    document.addEventListener('click', function (e) {
        const boton = e.target.closest('.ver-mas');
        if (!boton) return;

        const tabla = document.querySelector('[data-lista="' + boton.dataset.objetivo + '"]');
        if (!tabla) return;

        const ocultas = tabla.querySelectorAll('tr.extra.oculto');

        if (ocultas.length) {
            ocultas.forEach(function (fila) { fila.classList.remove('oculto'); });
            boton.textContent = 'Ver menos';
        } else {
            tabla.querySelectorAll('tr.extra').forEach(function (fila) { fila.classList.add('oculto'); });
            boton.textContent = 'Ver los ' + boton.dataset.ocultas + ' restantes';
        }
    });
</script>
