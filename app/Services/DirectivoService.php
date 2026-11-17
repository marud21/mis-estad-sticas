<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Altas, cambios y bajas de las cuentas de directivo.
 *
 * Todo lo que escribe pasa por aqui, y todo comprueba primero que la
 * cuenta sea de directivo. La comprobacion esta repetida a proposito:
 * ya la hace el controlador, pero este servicio es lo unico que toca la
 * tabla de usuarios y no debe poder tocar una cuenta de administrador
 * ni por error de quien lo llame mañana.
 *
 * El rol nunca se toma de lo que llega del formulario: se fija aqui. Si
 * viniera de afuera, un campo "role" colado en la peticion convertiria
 * a un directivo en administrador.
 */
class DirectivoService
{
    public function crear(array $datos): User
    {
        return User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'role' => User::ROLE_DIRECTIVO,
        ]);
    }

    /** Cambia nombre y correo. No toca la contrasena ni el rol. */
    public function actualizar(User $directivo, array $datos): User
    {
        $this->asegurarQueEsDirectivo($directivo);

        $directivo->update([
            'name' => $datos['name'],
            'email' => $datos['email'],
        ]);

        return $directivo;
    }

    /**
     * Le asigna una contrasena nueva y lo saca de donde estuviera metido.
     *
     * Cambiar la clave no cierra por si solo las sesiones que ya estaban
     * abiertas: Laravel reconoce al usuario por su id, no por su clave.
     * Si la razon del cambio es que alguien mas entro a la cuenta, ese
     * alguien seguiria dentro. Por eso se le borran las sesiones y se le
     * anula el token de "recordarme", que es la otra forma de volver a
     * entrar sin escribir nada.
     */
    public function cambiarPassword(User $directivo, string $password): void
    {
        $this->asegurarQueEsDirectivo($directivo);

        DB::transaction(function () use ($directivo, $password) {
            $directivo->forceFill([
                'password' => $password,
                'remember_token' => null,
            ])->save();

            $this->cerrarSesiones($directivo);
        });
    }

    /**
     * Borra la cuenta.
     *
     * Lo que el directivo hubiera registrado no se borra: las llaves de
     * noticias, sanciones, cierres de caja, ajustes de puntos y carrusel
     * estan en SET NULL, asi que esos registros se quedan y solo pierden
     * el nombre de quien los hizo. El cierre de caja pasa a decir
     * "Sistema", que es lo que ya mostraba cuando no habia usuario.
     *
     * Las sesiones hay que borrarlas aparte: esa tabla guarda el user_id
     * pero no tiene llave foranea, asi que no se limpian solas y
     * quedarian apuntando a una cuenta que ya no existe.
     */
    public function eliminar(User $directivo): void
    {
        $this->asegurarQueEsDirectivo($directivo);

        DB::transaction(function () use ($directivo) {
            $this->cerrarSesiones($directivo);
            $directivo->delete();
        });
    }

    /**
     * Cuantos registros quedarian sin nombre si se borra la cuenta.
     *
     * Es solo lectura y sirve para avisarle al administrador antes de
     * que confirme. Se consulta con el constructor de consultas y no por
     * relaciones para no tener que declarar seis relaciones en el modelo
     * de usuario que no se usan en ningun otro lado.
     *
     * @return array<string, int>
     */
    public function datosAsociados(User $directivo): array
    {
        $tablas = [
            'noticias' => 'Noticias publicadas',
            'sanciones' => 'Sanciones registradas',
            'cierres_caja' => 'Cierres de caja',
            'ajustes_puntos' => 'Ajustes de puntos',
            'carrusel_imagenes' => 'Imagenes del carrusel',
        ];

        $cuentas = [];
        foreach ($tablas as $tabla => $etiqueta) {
            $cuantos = DB::table($tabla)->where('user_id', $directivo->id)->count();
            if ($cuantos > 0) {
                $cuentas[$etiqueta] = $cuantos;
            }
        }

        // Los cierres de caja apuntan al usuario dos veces: quien lo
        // guardo y quien lo anulo.
        $anulados = DB::table('cierres_caja')->where('anulado_por', $directivo->id)->count();
        if ($anulados > 0) {
            $cuentas['Cierres de caja anulados'] = $anulados;
        }

        return $cuentas;
    }

    /**
     * Sesiones abiertas de esa cuenta, para mostrarlas en pantalla.
     *
     * Devuelve null cuando las sesiones no se guardan en la base, porque
     * entonces no hay forma de saberlo desde aqui y mostrar un cero
     * seria afirmar algo que no se comprobo.
     */
    public function sesionesAbiertas(User $directivo): ?int
    {
        if (! $this->sesionesEnLaBase()) {
            return null;
        }

        return DB::table('sessions')->where('user_id', $directivo->id)->count();
    }

    /**
     * Cerrarle la sesion depende de que las sesiones esten en la base,
     * que es como esta configurado el sistema (SESSION_DRIVER=database)
     * tanto aqui como en produccion. Si algun dia se cambia a archivos o
     * a cookies, esto deja de cerrar nada y hay que resolverlo de otra
     * forma; por eso se comprueba en vez de borrar a ciegas.
     */
    private function cerrarSesiones(User $directivo): void
    {
        if (! $this->sesionesEnLaBase()) {
            return;
        }

        DB::table('sessions')->where('user_id', $directivo->id)->delete();
    }

    private function sesionesEnLaBase(): bool
    {
        return config('session.driver') === 'database';
    }

    private function asegurarQueEsDirectivo(User $usuario): void
    {
        if (! $usuario->esDirectivo()) {
            throw new RuntimeException(
                'Este servicio solo administra cuentas de directivo, y se le paso un usuario con rol "'
                .$usuario->role.'".'
            );
        }
    }
}
