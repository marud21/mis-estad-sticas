<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocioRequest;
use App\Models\Equipo;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Services\EquipoService;
use App\Services\ImagenService;
use App\Services\SocioService;
use App\Support\AgrupadorFinanciero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocioController extends Controller
{
    public function __construct(
        private readonly SocioService $socios,
        private readonly ImagenService $imagenes,
        private readonly EquipoService $equipos,
    ) {
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->trim()->toString();
        $soloMultiEquipo = $request->boolean('multi_equipo');

        // Solo se acepta un estado conocido: cualquier otro valor se ignora
        // y se listan todos los socios.
        $estado = $request->string('estado')->trim()->toString();
        $estado = in_array($estado, Socio::ESTADOS, true) ? $estado : '';

        $socios = Socio::with('equipos')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('numero_documento', 'like', "%{$q}%");
                });
            })
            ->when($soloMultiEquipo, fn ($query) => $query->has('equipos', '>', 1))
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->orderBy('nombre_completo')
            ->paginate(15);

        return view('socios.index', compact('socios', 'estado'));
    }

    public function create()
    {
        $tiposCargo = TipoCargo::orderBy('nombre')->get();
        $equipos = Equipo::orderBy('nombre')->get();

        return view('socios.create', compact('tiposCargo', 'equipos'));
    }

    public function store(SocioRequest $request)
    {
        $datos = $request->safe()->except(['cargos', 'foto', 'equipo_id']);
        $cargos = collect($request->input('cargos', []))
            ->filter(fn ($cargo) => filled($cargo['tipo_cargo_id'] ?? null))
            ->all();

        if ($request->hasFile('foto')) {
            $datos['foto_path'] = $this->imagenes->guardarComprimida($request->file('foto'), 'socios');
        }

        $socio = $this->socios->crear($datos, $cargos, $request->integer('equipo_id') ?: null);

        return redirect()->route('socios.show', $socio)->with('status', 'Socio registrado correctamente.');
    }

    public function show(Socio $socio)
    {
        $socio->load(['equipos', 'cargos.tipoCargo', 'cargos.torneo', 'cargos.equipo', 'pagos.cargo', 'pagos.torneo', 'pagos.equipo']);

        $cargosPorAnio = AgrupadorFinanciero::porAnioYTorneo($socio->cargos);
        $pagosPorAnio = AgrupadorFinanciero::porAnioYTorneo($socio->pagos);

        $equiposDisponibles = Equipo::whereDoesntHave('socios', fn ($q) => $q->where('socios.id', $socio->id))
            ->orderBy('nombre')
            ->get();

        return view('socios.show', compact('socio', 'cargosPorAnio', 'pagosPorAnio', 'equiposDisponibles'));
    }

    public function edit(Socio $socio)
    {
        $socio->load('equipos');
        $equipos = Equipo::orderBy('nombre')->get();

        return view('socios.edit', compact('socio', 'equipos'));
    }

    public function update(SocioRequest $request, Socio $socio)
    {
        $datos = $request->safe()->except(['cargos', 'foto', 'equipo_id']);

        if ($request->hasFile('foto')) {
            if ($socio->foto_path) {
                Storage::disk('public')->delete($socio->foto_path);
            }

            $datos['foto_path'] = $this->imagenes->guardarComprimida($request->file('foto'), 'socios');
        }

        $this->socios->actualizar($socio, $datos, $request->integer('equipo_id') ?: null, $request->has('equipo_id'));

        return redirect()->route('socios.show', $socio)->with('status', 'Socio actualizado correctamente.');
    }

    public function destroy(Socio $socio)
    {
        $this->socios->eliminar($socio);

        return redirect()->route('socios.index')->with('status', 'Socio eliminado.');
    }

    public function cambiarEstado(Socio $socio)
    {
        request()->validate(['estado' => 'required|in:activo,suspendido,retirado,excluido']);

        $this->socios->cambiarEstado($socio, request('estado'));

        return back()->with('status', 'Estado del socio actualizado.');
    }

    public function agregarEquipo(Socio $socio)
    {
        request()->validate(['equipo_id' => 'required|exists:equipos,id']);

        $equipo = Equipo::findOrFail(request('equipo_id'));
        $reactivado = $this->equipos->agregarSocio($equipo, $socio);

        return back()->with('status', $reactivado
            ? 'Equipo agregado al socio y se reactivo (estaba retirado).'
            : 'Equipo agregado al socio.');
    }

    public function quitarEquipo(Socio $socio, Equipo $equipo)
    {
        $retirado = $this->equipos->quitarSocio($equipo, $socio);

        return back()->with('status', $retirado
            ? 'Equipo retirado del socio. Como quedo sin equipo, se marco como retirado y deja de entrar en los cobros.'
            : 'Equipo retirado del socio.');
    }
}
