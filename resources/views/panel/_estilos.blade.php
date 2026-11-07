{{-- Estilos del panel. Los graficos son CSS puro: ni una libreria. --}}
<style>
    .panel-filtros .grid-5 { align-items: end; }
    .panel-filtros input, .panel-filtros select { margin-bottom: 0; }
    .panel-filtros-pie {
        display: flex; align-items: center; gap: 10px;
        flex-wrap: wrap; margin-top: 14px;
    }
    .atajos { display: flex; gap: 10px; flex-wrap: wrap; }
    .atajos a { font-size: 13px; }
    .panel-rango { margin-left: auto; font-size: 13px; color: #666; }
    .panel-sub { margin: -6px 0 14px; font-size: 13px; color: #666; }
    .panel-vacio { color: #999; font-size: 13px; }
    .panel-porque { color: #666; font-size: 13px; }

    /* ---------- Las cuatro cifras de arriba ---------- */
    .cifras { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 18px; }
    .cifras > * { min-width: 0; }
    .cifra {
        background: var(--blanco);
        border: 1px solid var(--gris-borde);
        border-top: 3px solid var(--azul-claro);
        border-radius: 8px;
        padding: 14px 16px;
        display: flex; flex-direction: column; gap: 2px;
    }
    .cifra-alerta { border-top-color: var(--rojo); }
    .cifra-bien { border-top-color: var(--verde); }
    .cifra-rotulo { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: .3px; }
    .cifra-valor { font-size: 24px; color: var(--azul-oscuro); line-height: 1.2; }
    .cifra-nota { font-size: 12px; color: #888; }

    /* ---------- Cifras pequeñas dentro de una seccion ---------- */
    .panel-mini > div {
        border: 1px solid var(--gris-borde);
        border-radius: 6px;
        padding: 10px 12px;
        display: flex; flex-direction: column; gap: 2px;
    }
    .panel-mini strong { font-size: 18px; color: var(--azul-oscuro); }
    .mini-rotulo { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: .3px; }
    .mini-nota { font-size: 11px; color: #888; }

    /* ---------- Grafico de barras por mes ---------- */
    .barras {
        display: flex; align-items: flex-end; gap: 6px;
        height: 170px; margin-top: 10px;
        padding: 10px 4px 0;
        border-bottom: 1px solid var(--gris-borde);
        overflow-x: auto;
    }
    .barra-col {
        flex: 1 0 44px;
        display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
        height: 100%;
    }
    .barra {
        width: 100%;
        max-width: 42px;
        background: var(--azul-claro);
        border-radius: 4px 4px 0 0;
        transition: background .2s;
    }
    .barra-col:hover .barra { background: var(--azul-oscuro); }
    .barra-valor { font-size: 11px; color: #666; margin-bottom: 4px; white-space: nowrap; }
    .barra-eje { font-size: 10px; color: #888; margin-top: 6px; white-space: nowrap; }

    /* ---------- Barras de proporcion ---------- */
    .proporcion { margin-bottom: 10px; }
    .proporcion-cabeza {
        display: flex; justify-content: space-between; gap: 10px;
        font-size: 13px; margin-bottom: 4px;
    }
    .proporcion-riel { height: 8px; background: var(--gris-claro); border-radius: 4px; overflow: hidden; }
    .proporcion-relleno { height: 100%; background: var(--azul-claro); border-radius: 4px; }
    .proporcion-relleno.estado-activo { background: var(--verde); }
    .proporcion-relleno.estado-suspendido { background: var(--naranja); }
    .proporcion-relleno.estado-retirado { background: #9aa6b2; }
    .proporcion-relleno.estado-excluido { background: var(--rojo); }

    /* ---------- Tablas compactas ---------- */
    .panel-tabla { width: 100%; border-collapse: collapse; font-size: 14px; }
    .panel-tabla th {
        text-align: left; font-size: 11px; text-transform: uppercase;
        letter-spacing: .3px; color: #666; padding: 6px 8px;
        border-bottom: 1px solid var(--gris-borde);
    }
    .panel-tabla td { padding: 7px 8px; border-bottom: 1px solid var(--gris-borde); }
    .panel-tabla tr:last-child td { border-bottom: 0; }
    .panel-tabla .n { text-align: right; white-space: nowrap; font-weight: 600; }
    .rojo { color: var(--rojo); }
    .amarilla { color: var(--naranja); }

    @media (max-width: 1000px) {
        .cifras { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 640px) {
        .cifras { grid-template-columns: 1fr; }
        .panel-rango { margin-left: 0; width: 100%; }
        .barra-col { flex-basis: 38px; }
    }
</style>
