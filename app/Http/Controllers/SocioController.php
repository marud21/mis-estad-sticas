<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocioRequest;
use App\Models\Equipo;
use App\Models\Socio;
use App\Models\TipoCargo;
use App\Services\SocioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SocioController extends Controller
{
    public function __construct(private readonly SocioService $socios)
    {
    }

    public function index(Request $request)
    {
        $q = $request->string('q')->trim()->toString();

        $socios = Socio::with('equipos')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre_completo', 'like', "%{$q}%")
                        ->orWhere('numero_documento', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre_completo')
            ->paginate(15);

        return view('socios.index', compact('socios'));
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
            $datos['foto_path'] = $request->file('foto')->store('socios', 'public');
        }

        $socio = $this->socios->crear($datos, $cargos, $request->integer('equipo_id') ?: null);

        return redirect()->route('socios.show', $socio)->with('status', 'Socio registrado correctamente.');
    }

    public function show(Socio $socio)
    {
        $socio->load(['equipos', 'cargos.tipoCargo', 'pagos.cargo']);

        return view('socios.show', compact('socio'));
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

            $datos['foto_path'] = $request->file('foto')->store('socios', 'public');
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
        request()->validate(['estado' => 'required|in:activo,suspendido,retirado']);

        $this->socios->cambiarEstado($socio, request('estado'));

        return back()->with('status', 'Estado del socio actualizado.');
    }
}
