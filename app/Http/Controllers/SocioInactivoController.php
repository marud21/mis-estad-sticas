<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Socio;
use App\Services\SocioInactivoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocioInactivoController extends Controller
{
    public function __construct(private readonly SocioInactivoService $inactivos)
    {
    }

    public function index(Request $request)
    {
        $datos = $request->validate([
            'meses' => ['nullable', 'integer', 'min:1', 'max:60'],
            'q' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', Rule::in(SocioInactivoService::ESTADOS_VIGILADOS)],
            'equipo_id' => ['nullable', 'string', 'max:20'],
            'solo_sin_pagos' => ['nullable', 'boolean'],
            'con_fecha_adelantada' => ['nullable', 'boolean'],
        ]);

        $meses = $datos['meses'] ?? SocioInactivoService::MESES_LIMITE;

        $filtros = [
            'q' => trim((string) ($datos['q'] ?? '')),
            'estado' => (string) ($datos['estado'] ?? ''),
            'equipo_id' => (string) ($datos['equipo_id'] ?? ''),
            'solo_sin_pagos' => $request->boolean('solo_sin_pagos'),
            'con_fecha_adelantada' => $request->boolean('con_fecha_adelantada'),
        ];

        $socios = $this->inactivos->paginar($meses, $filtros);

        // Los totales son del conjunto filtrado entero, no de la pagina
        // que se esta viendo.
        $resumen = $this->inactivos->resumen($meses, $filtros);

        // Y el total sin filtrar, para poder decir "12 de 79" y que se
        // note que lo que se esta viendo es un recorte.
        $totalSinFiltrar = $this->inactivos->contar($meses);

        $equipos = Equipo::orderBy('nombre')->get(['id', 'nombre', 'categoria']);

        $hayFiltros = $filtros['q'] !== ''
            || $filtros['estado'] !== ''
            || $filtros['equipo_id'] !== ''
            || $filtros['solo_sin_pagos']
            || $filtros['con_fecha_adelantada'];

        return view('socios-inactivos.index', [
            'socios' => $socios,
            'resumen' => $resumen,
            'meses' => $meses,
            'filtros' => $filtros,
            'equipos' => $equipos,
            'totalSinFiltrar' => $totalSinFiltrar,
            'hayFiltros' => $hayFiltros,
            'estados' => Socio::ESTADOS_ETIQUETAS,
        ]);
    }
}
