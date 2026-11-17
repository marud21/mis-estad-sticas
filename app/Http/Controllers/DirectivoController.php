<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectivoRequest;
use App\Http\Requests\EliminarDirectivoRequest;
use App\Http\Requests\PasswordDirectivoRequest;
use App\Models\User;
use App\Services\DirectivoService;

/**
 * Las cuentas de directivo, administradas por el administrador.
 *
 * Estas rutas no estan en la lista blanca de App\Support\Permisos, asi
 * que el middleware ya le cierra la puerta al directivo: no puede darse
 * permisos a si mismo ni tocar la cuenta de un compañero. Aun asi cada
 * accion lo vuelve a comprobar, porque el dia que alguien agregue
 * "directivos.*" a esa lista sin pensarlo, esto tiene que seguir cerrado.
 */
class DirectivoController extends Controller
{
    public function __construct(private readonly DirectivoService $directivos) {}

    public function index()
    {
        $this->soloAdministrador();

        $directivos = User::where('role', User::ROLE_DIRECTIVO)
            ->orderBy('name')
            ->get()
            ->map(fn (User $directivo) => [
                'modelo' => $directivo,
                // Cuantos registros se quedarian sin el nombre de quien
                // los hizo si se borra la cuenta. Se muestra antes de
                // confirmar, no despues.
                'datos' => $this->directivos->datosAsociados($directivo),
                'sesiones' => $this->directivos->sesionesAbiertas($directivo),
            ]);

        return view('directivos.index', compact('directivos'));
    }

    public function store(DirectivoRequest $request)
    {
        $this->soloAdministrador();

        $directivo = $this->directivos->crear($request->validated());

        return redirect()
            ->route('directivos.index')
            ->with('status', 'Cuenta de directivo creada para '.$directivo->name.'.');
    }

    public function update(DirectivoRequest $request, User $directivo)
    {
        $this->soloAdministrador();
        $this->asegurarQueEsDirectivo($directivo);

        $this->directivos->actualizar($directivo, $request->validated());

        return redirect()
            ->route('directivos.index')
            ->with('status', 'Datos de '.$directivo->name.' actualizados.');
    }

    public function password(PasswordDirectivoRequest $request, User $directivo)
    {
        $this->soloAdministrador();
        $this->asegurarQueEsDirectivo($directivo);

        $this->directivos->cambiarPassword($directivo, $request->validated('password'));

        return redirect()
            ->route('directivos.index')
            ->with('status', 'Contraseña nueva asignada a '.$directivo->name
                .'. Si tenia una sesion abierta, se cerro.');
    }

    public function destroy(EliminarDirectivoRequest $request, User $directivo)
    {
        $this->soloAdministrador();
        $this->asegurarQueEsDirectivo($directivo);

        $nombre = $directivo->name;
        $this->directivos->eliminar($directivo);

        return redirect()
            ->route('directivos.index')
            ->with('status', 'Cuenta de '.$nombre.' eliminada. Lo que hubiera registrado se conserva.');
    }

    private function soloAdministrador(): void
    {
        abort_unless(
            auth()->user()?->esAdmin(),
            403,
            'Solo el administrador gestiona las cuentas de directivo.',
        );
    }

    /**
     * Que el id de la direccion sea de verdad un directivo.
     *
     * Sin esto, /directivos/1 cargaria al administrador y esta pantalla
     * serviria para cambiarle el correo, la clave o borrarlo. Se responde
     * 404 y no 403 a proposito: desde aqui esa cuenta no existe.
     */
    private function asegurarQueEsDirectivo(User $usuario): void
    {
        abort_unless($usuario->esDirectivo(), 404);
    }
}
