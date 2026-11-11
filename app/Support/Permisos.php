<?php

namespace App\Support;

use App\Models\User;

/**
 * Que puede hacer cada rol, en un solo lugar.
 *
 * Se define por nombre de ruta y no por controlador ni por menu, porque
 * el nombre de ruta es lo unico que cubre todas las puertas: el menu se
 * puede esconder, pero la direccion sigue ahi si alguien la escribe.
 *
 * El administrador no tiene lista: puede todo. El directivo trabaja con
 * una lista blanca, asi que una ruta nueva le queda cerrada hasta que
 * alguien decida abrirsela. Es a proposito: olvidar abrir una pantalla
 * se nota enseguida, olvidar cerrarla no.
 */
class Permisos
{
    /**
     * Rutas que puede usar un directivo.
     *
     * El asterisco cubre el grupo entero. Sin asterisco, es esa ruta y
     * nada mas, que es como quedan socios y equipos: solo consulta.
     *
     * @var array<int, string>
     */
    private const DIRECTIVO = [
        // --- Finanzas, sin los reportes contables ---
        // Los tipos de cargo se administran, pero NO se aplican ni se
        // modifican en masa: eso le crearia o cambiaria cargos a cientos
        // de socios, que es justo lo que no le corresponde.
        'tipos-cargo.index',
        'tipos-cargo.create',
        'tipos-cargo.store',
        'tipos-cargo.edit',
        'tipos-cargo.update',
        'tipos-cargo.destroy',
        // De cuota moderada puede ver y ajustar el porcentaje, que es solo
        // un ajuste. Recalcular no: eso reescribe la cuota de todos los
        // socios de una vez, igual que la carga masiva.
        'cuota-moderada.index',
        'cuota-moderada.porcentaje',
        'cierre-caja.*',
        // De los cargos duplicados puede ver el listado, pero no la
        // limpieza: borra cargos en masa, igual que las demas operaciones
        // masivas que le quedan cerradas.
        'cargos-duplicados.index',

        // --- Sistema, sin importar datos ---
        // Importar reemplaza socios, cargos y pagos de forma masiva. Un
        // rol que no puede editar un socio tampoco deberia poder
        // reemplazar cientos de una vez.
        'configuracion.*',
        'password.*',

        // --- Noticias ---
        'noticias-admin.*',

        // --- Socios: solo consultar. Ninguna ruta que escriba. ---
        'socios.index',
        'socios.show',
        'socios.reporte',
        'socios.whatsapp',
        'pagos.recibo',
        'pagos.recibo-pdf',

        // --- Equipos: solo consultar. Los informes no cambian datos. ---
        'equipos.index',
        'equipos.show',
        'equipos.reporte',
        'equipos.reporte.multiples',
        'equipos.planilla-pagos',

        // --- Siempre ---
        'logout',
    ];

    /** Rutas en las que el directivo solo mira, para esconderle los botones. */
    private const SOLO_LECTURA = ['socios', 'equipos'];

    public static function puede(?User $usuario, ?string $ruta): bool
    {
        if (! $usuario) {
            return false;
        }

        // El administrador pasa siempre, y se comprueba antes que nada:
        // una ruta nueva que alguien olvide nombrar no debe dejarlo fuera
        // de su propio sistema.
        if ($usuario->esAdmin()) {
            return true;
        }

        if (blank($ruta)) {
            return false;
        }

        if (! $usuario->esDirectivo()) {
            // Un rol desconocido no se trata como administrador: se le
            // cierra todo menos salir, y asi se nota el problema.
            return $ruta === 'logout';
        }

        foreach (self::DIRECTIVO as $permitida) {
            if (self::coincide($permitida, $ruta)) {
                return true;
            }
        }

        return false;
    }

    /** Si el usuario solo puede mirar el modulo al que pertenece la ruta. */
    public static function soloLectura(?User $usuario, ?string $ruta = null): bool
    {
        if (! $usuario || ! $usuario->esDirectivo()) {
            return false;
        }

        $ruta ??= request()->route()?->getName();
        $modulo = $ruta ? strtok($ruta, '.') : null;

        return in_array($modulo, self::SOLO_LECTURA, true);
    }

    private static function coincide(string $patron, string $ruta): bool
    {
        if (str_ends_with($patron, '.*')) {
            $grupo = substr($patron, 0, -2);

            return $ruta === $grupo || str_starts_with($ruta, $grupo.'.');
        }

        return $ruta === $patron;
    }
}
