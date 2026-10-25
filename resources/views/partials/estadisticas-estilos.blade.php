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
