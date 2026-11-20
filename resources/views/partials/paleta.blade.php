{{--
    Los colores del sistema, en un solo sitio.

    Antes esta misma lista estaba copiada en seis archivos (el layout, la
    portada, el ingreso, la consulta publica y las dos de noticias), asi
    que cambiar un tono obligaba a tocarlos todos y era cuestion de
    tiempo que alguno se quedara atras.

    No lleva etiqueta <style> a proposito: se incluye DENTRO del <style>
    que ya tiene cada pagina. Una etiqueta <style> dentro de otra no es
    valida y el navegador la descarta.

    --- Los colores de la corporacion ---
      verde    #006d36  el predominante
      blanco   #ffffff  el segundo
      amarillo #f9f002  el tercero

    El amarillo sobre blanco no se lee: su contraste es de 1,2 a 1,
    cuando lo minimo legible es 4,5. Por eso nunca va como texto ni como
    fondo de un texto claro. Se usa en trazos sobre el verde, donde
    contrasta 5,4 a 1, y siempre que lleve texto encima ese texto va
    oscuro (14,5 a 1).
--}}
:root {
    /*
     * Marca. Los tres verdes son el mismo color a distintas
     * profundidades; todos se leen con texto blanco encima:
     *   oscura 10,1 a 1   |   marca 6,5 a 1   |   media 5,0 a 1
     */
    --marca-oscura: #004d26;
    --marca: #006d36;
    --marca-media: #008043;
    --acento: #f9f002;
    --blanco: #ffffff;

    /* Neutros con un apunte de verde, para que no se vean frios
       al lado de la marca. */
    --gris-claro: #f2f6f3;
    --gris-borde: #d9e3dc;
    --verde-palido: #e8f3ec;

    /*
     * Estados. Estos NO son colores de marca y por eso no se tiñen de
     * verde: el rojo de una deuda y el naranja de una suspension tienen
     * que significar lo mismo aqui que en cualquier otro sistema.
     */
    --rojo: #b3261e;
    --verde: #1e7a3e;
    --naranja: #b56a00;
    --neutro: #51606e;

    /*
     * El amarillo de las tarjetas del futbol. No es el de la
     * corporacion: va mas apagado justamente para que no se confunda
     * con el, y porque tiene que leerse sobre fondo claro.
     */
    --amarillo: #d4a017;
}
