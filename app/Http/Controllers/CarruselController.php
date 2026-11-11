<?php

namespace App\Http\Controllers;

use App\Http\Requests\CarruselImagenRequest;
use App\Models\CarruselImagen;
use App\Services\CarruselService;
use Illuminate\Http\Request;

class CarruselController extends Controller
{
    public function __construct(private readonly CarruselService $carrusel)
    {
    }

    public function index()
    {
        return view('carrusel.index', [
            'imagenes' => CarruselImagen::orderBy('orden')->orderBy('id')->get(),
            'reglas' => CarruselService::reglas(),
        ]);
    }

    public function store(CarruselImagenRequest $request)
    {
        $this->carrusel->guardar(
            $request->file('imagen'),
            $request->validated(),
            $request->user()?->id,
        );

        return redirect()->route('carrusel.index')
            ->with('status', 'Imagen agregada al carrusel.');
    }

    public function update(Request $request, CarruselImagen $carrusel)
    {
        $datos = $request->validate([
            'titulo' => ['nullable', 'string', 'max:120'],
            'enlace' => ['nullable', 'url', 'max:255'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:999'],
            'publicado' => ['sometimes', 'boolean'],
        ]);

        $this->carrusel->actualizar($carrusel, $datos);

        return redirect()->route('carrusel.index')->with('status', 'Imagen actualizada.');
    }

    public function destroy(CarruselImagen $carrusel)
    {
        $this->carrusel->eliminar($carrusel);

        return redirect()->route('carrusel.index')->with('status', 'Imagen eliminada del carrusel.');
    }
}
