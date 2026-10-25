{{--
    Boton que despliega las filas ocultas de una lista larga.
    Espera: $lista (identificador), $total (filas), $tope (cuantas se ven).
--}}
@if ($total > $tope)
    <button type="button" class="ver-mas" data-objetivo="{{ $lista }}"
            data-ocultas="{{ $total - $tope }}" data-total="{{ $total }}">
        Ver los {{ $total - $tope }} restantes
    </button>
@endif
