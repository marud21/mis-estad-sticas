<?php

namespace App\Http\Controllers;

use App\Http\Requests\EquipoRequest;
use App\Models\Equipo;
use App\Models\Socio;
use App\Services\EquipoService;
use Illuminate\Http\Request;

class EquipoController extends Controller
{
    public function __construct(private readonly EquipoService $equipos)
    {
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->trim()->toString();

        $equipos = Equipo::with('torneo')->withCount('socios')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('categoria', 'like', "%{$q}%")
                        ->orWhereHas('torneo', fn ($t) => $t->where('nombre', 'like', "%{$q}%"));
                });
            })
            ->orderBy('nombre')
            ->paginate(15);

        return view('equipos.index', compact('equipos'));
    }

    public function create()
    {
        return view('equipos.create');
    }

    public function store(EquipoRequest $request)
    {
        $equipo = $this->equipos->crear($request->validated());

        return redirect()->route('equipos.show', $equipo)->with('status', 'Equipo creado correctamente.');
    }

    public function show(Equipo $equipo)
    {
        $equipo->load(['socios', 'torneo']);

        // La deuda que se muestra en la planilla del equipo es la que genera
        // ese equipo en particular (sus propios cargos y pagos), no la
        // deuda global del socio en toda la corporacion.
        $equipo->socios->each(function (Socio $socio) use ($equipo) {
            $cargosEquipo = (float) $socio->cargos()->where('equipo_id', $equipo->id)->sum('monto');
            $pagosEquipo = (float) $socio->pagos()->where('equipo_id', $equipo->id)->sum('valor');
            $socio->deuda_equipo = $cargosEquipo - $pagosEquipo;
        });

        $sociosDisponibles = Socio::with('equipos')
            ->whereDoesntHave('equipos', fn ($q) => $q->where('equipos.id', $equipo->id))
            ->orderBy('nombre_completo')
            ->get();

        return view('equipos.show', compact('equipo', 'sociosDisponibles'));
    }

    public function edit(Equipo $equipo)
    {
        return view('equipos.edit', compact('equipo'));
    }

    public function update(EquipoRequest $request, Equipo $equipo)
    {
        $this->equipos->actualizar($equipo, $request->validated());

        return redirect()->route('equipos.show', $equipo)->with('status', 'Equipo actualizado correctamente.');
    }

    public function destroy(Equipo $equipo)
    {
        $this->equipos->eliminar($equipo);

        return redirect()->route('equipos.index')->with('status', 'Equipo eliminado.');
    }

    public function agregarSocio(Equipo $equipo)
    {
        request()->validate(['socio_id' => 'required|exists:socios,id']);

        $socio = Socio::findOrFail(request('socio_id'));
        $this->equipos->agregarSocio($equipo, $socio);

        return back()->with('status', 'Jugador agregado al equipo.');
    }

    public function quitarSocio(Equipo $equipo, Socio $socio)
    {
        $this->equipos->quitarSocio($equipo, $socio);

        return back()->with('status', 'Jugador retirado del equipo.');
    }

    public function cambiarEstado(Equipo $equipo)
    {
        request()->validate(['estado' => 'required|in:activo,inactivo']);

        $this->equipos->cambiarEstado($equipo, request('estado'));

        $mensaje = request('estado') === Equipo::ESTADO_INACTIVO
            ? 'Equipo marcado como inactivo. Sus socios activos quedaron suspendidos.'
            : 'Equipo marcado como activo. Los socios suspendidos por el equipo fueron reactivados.';

        return back()->with('status', $mensaje);
    }
}
