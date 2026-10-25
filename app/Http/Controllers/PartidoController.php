<?php

namespace App\Http\Controllers;

use App\Http\Requests\AjustePuntosRequest;
use App\Http\Requests\PartidoRequest;
use App\Http\Requests\RegistroPartidoRequest;
use App\Models\AjustePuntos;
use App\Models\Equipo;
use App\Models\Partido;
use App\Models\Socio;
use App\Models\Torneo;
use App\Services\PartidoService;
use App\Services\RegistroPartidoService;
use App\Services\ReporteService;
use App\Services\TablaPosicionesService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PartidoController extends Controller
{
    public function __construct(
        private readonly TablaPosicionesService $posiciones,
        private readonly RegistroPartidoService $registros,
        private readonly PartidoService $partidos,
    ) {
    }

    public function index(Request $request)
    {
        $torneos = Torneo::orderByDesc('fecha_inicio')->orderBy('nombre')->get();

        // Solo se acepta un torneo existente: cualquier otro valor se ignora.
        $torneoId = $request->integer('torneo_id');
        $torneo = $torneos->firstWhere('id', $torneoId) ?? $torneos->first();

        $estado = $request->string('estado')->trim()->toString();
        $estado = array_key_exists($estado, Partido::ESTADOS) ? $estado : '';

        $partidos = Partido::with(['equipoLocal', 'equipoVisitante'])
            ->when($torneo, fn ($q) => $q->where('torneo_id', $torneo->id))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->orderByDesc('fecha_hora')
            ->paginate(20);

        return view('partidos.index', compact('partidos', 'torneos', 'torneo', 'estado'));
    }

    public function create(Request $request)
    {
        $torneos = Torneo::orderBy('nombre')->get();
        $torneo = $torneos->firstWhere('id', $request->integer('torneo_id')) ?? $torneos->first();

        return view('partidos.create', [
            'torneos' => $torneos,
            'torneo' => $torneo,
            'equipos' => $this->equiposDe($torneo),
        ]);
    }

    public function store(PartidoRequest $request)
    {
        $partido = $this->partidos->crear($request->validated());

        return redirect()->route('partidos.index', ['torneo_id' => $partido->torneo_id])
            ->with('status', 'Partido programado correctamente.');
    }

    public function edit(Partido $partido)
    {
        return view('partidos.edit', [
            'partido' => $partido,
            'torneos' => Torneo::orderBy('nombre')->get(),
            'equipos' => $this->equiposDe($partido->torneo),
        ]);
    }

    public function update(PartidoRequest $request, Partido $partido)
    {
        $this->partidos->actualizar($partido, $request->validated());

        return redirect()->route('partidos.index', ['torneo_id' => $partido->torneo_id])
            ->with('status', 'Partido actualizado.');
    }

    public function destroy(Partido $partido)
    {
        $torneoId = $partido->torneo_id;
        $this->partidos->eliminar($partido);

        return redirect()->route('partidos.index', ['torneo_id' => $torneoId])
            ->with('status', 'Partido eliminado.');
    }

    /** Pantalla para cargar goles y tarjetas jugador por jugador. */
    public function registro(Partido $partido)
    {
        $partido->load(['equipoLocal', 'equipoVisitante', 'incidencias']);

        return view('partidos.registro', [
            'partido' => $partido,
            'jugadoresLocal' => $this->jugadoresDe($partido->equipoLocal),
            'jugadoresVisitante' => $this->jugadoresDe($partido->equipoVisitante),
        ]);
    }

    public function guardarRegistro(RegistroPartidoRequest $request, Partido $partido)
    {
        $incidencias = $request->incidencias();

        // Aviso de doble cobro: si algun socio ya tiene una tarjeta cobrada
        // ese mismo dia por fuera de este partido, se confirma antes.
        if (! $request->boolean('confirmado')) {
            $enRiesgo = $this->registros->sociosConTarjetaCobradaEseDia($partido, $incidencias);

            if ($enRiesgo->isNotEmpty()) {
                return back()
                    ->withInput()
                    ->with('aviso_doble_cobro', $enRiesgo->all());
            }
        }

        $this->registros->registrar($partido, $incidencias);

        return redirect()->route('partidos.index', ['torneo_id' => $partido->torneo_id])
            ->with('status', "Resultado registrado: {$partido->fresh()->marcador}. Las tarjetas se cargaron a los socios.");
    }

    /**
     * Planilla de juego del partido: toma del propio partido los datos que
     * antes habia que escribir a mano (equipos, torneo, fecha, hora, lugar,
     * jornada y arbitro) y arma el PDF directamente.
     */
    public function planilla(Partido $partido, ReporteService $reportes)
    {
        $partido->load(['equipoLocal', 'equipoVisitante', 'torneo']);

        abort_unless($partido->equipoLocal && $partido->equipoVisitante, 404);

        $pdf = $reportes->planillaJuegoPdf($partido->equipoLocal, $partido->equipoVisitante, [
            'torneo' => $partido->torneo->nombre ?? null,
            'jornada' => $partido->fase,
            'cancha' => $partido->lugar,
            'fecha' => $partido->fecha_hora->toDateString(),
            'hora' => $partido->fecha_hora->format('h:i A'),
            'arbitro' => $partido->arbitro,
        ]);

        return $pdf->download(sprintf(
            'planilla-%s-vs-%s-%s.pdf',
            Str::slug($partido->equipoLocal->nombre),
            Str::slug($partido->equipoVisitante->nombre),
            $partido->fecha_hora->format('Y-m-d'),
        ));
    }

    public function posiciones(Request $request)
    {
        $torneos = Torneo::orderByDesc('fecha_inicio')->orderBy('nombre')->get();
        $torneo = $torneos->firstWhere('id', $request->integer('torneo_id')) ?? $torneos->first();

        $tabla = $torneo ? $this->posiciones->calcular($torneo) : collect();

        $partidosJugados = $torneo
            ? Partido::where('torneo_id', $torneo->id)->computables()->count()
            : 0;

        $ajustes = $torneo
            ? AjustePuntos::where('torneo_id', $torneo->id)->with('equipo')->orderByDesc('fecha')->orderByDesc('id')->get()
            : collect();

        $equipos = $this->equiposDe($torneo);

        return view('partidos.posiciones', compact('torneos', 'torneo', 'tabla', 'partidosJugados', 'ajustes', 'equipos'));
    }

    public function guardarAjuste(AjustePuntosRequest $request)
    {
        $ajuste = $this->posiciones->registrarAjuste($request->validated(), $request->user()?->id);

        return redirect()->route('partidos.posiciones', ['torneo_id' => $ajuste->torneo_id])
            ->with('status', "Ajuste de {$ajuste->puntos_con_signo} punto(s) aplicado a {$ajuste->equipo->nombre}.");
    }

    public function eliminarAjuste(AjustePuntos $ajuste)
    {
        $torneoId = $ajuste->torneo_id;
        $this->posiciones->eliminarAjuste($ajuste);

        return redirect()->route('partidos.posiciones', ['torneo_id' => $torneoId])
            ->with('status', 'Ajuste de puntos eliminado.');
    }

    /** Equipos inscritos en el torneo, que son los que pueden jugar. */
    private function equiposDe(?Torneo $torneo)
    {
        return $torneo
            ? Equipo::where('torneo_id', $torneo->id)->orderBy('nombre')->get()
            : collect();
    }

    /**
     * Jugadores que pueden anotarse en el partido: los mismos que salen en
     * la planilla de juego (activos, suspendidos y excluidos).
     */
    private function jugadoresDe(?Equipo $equipo)
    {
        return $equipo
            ? $equipo->socios()
                ->whereIn('estado', Socio::ESTADOS_EN_PLANILLA)
                ->orderByRaw('numero_camiseta IS NULL, numero_camiseta')
                ->orderBy('nombre_completo')
                ->get()
            : collect();
    }
}
