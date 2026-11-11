@extends('layouts.app')

@section('title', 'Carrusel')

@section('content')
    <x-breadcrumbs :items="['Carrusel' => null]" />

    <div class="card">
        <h1>Carrusel de la portada</h1>
        <p class="carrusel-sub">
            Las imagenes se muestran arriba de la pagina publica, una tras otra.
            Puedes dejar una cargada sin publicar y activarla cuando quieras.
        </p>

        {{-- ---------- Lo que se puede subir ---------- --}}
        <div class="reglas">
            <strong>Que se puede subir</strong>
            <ul>
                <li><b>Formatos:</b> {{ $reglas['formatos'] }}</li>
                <li>
                    <b>Resolucion recomendada:</b> {{ $reglas['resolucion'] }}
                    <span class="nota">(formato apaisado, mas ancho que alto)</span>
                </li>
                <li>
                    <b>Ancho minimo:</b> {{ $reglas['ancho_minimo'] }}
                    <span class="nota">(mas angosta se ve borrosa en pantallas grandes)</span>
                </li>
                <li>
                    <b>Peso maximo:</b> {{ $reglas['peso_estatica'] }} para JPG, PNG y WebP
                    &middot; <b>{{ $reglas['peso_gif'] }} para GIF</b>
                </li>
            </ul>
            <p class="nota">
                Las imagenes fijas se reducen y comprimen solas al subirlas, asi que puedes
                mandar una grande sin problema. <b>El GIF no se toca</b>, para que no pierda la
                animacion: lo que subes es exactamente lo que descarga cada visitante, por eso
                se le pide menos peso.
            </p>
        </div>

        {{-- ---------- Subir ---------- --}}
        <form action="{{ route('carrusel.store') }}" method="POST" enctype="multipart/form-data" class="subir">
            @csrf
            <div class="grid-3">
                <div>
                    <label for="imagen">Imagen o GIF</label>
                    <input type="file" name="imagen" id="imagen" required
                           accept=".jpg,.jpeg,.png,.webp,.gif">
                </div>
                <div>
                    <label for="titulo">Titulo (opcional)</label>
                    <input type="text" name="titulo" id="titulo" maxlength="120"
                           value="{{ old('titulo') }}" placeholder="Se usa como descripcion de la imagen">
                </div>
                <div>
                    <label for="enlace">Enlace (opcional)</label>
                    <input type="url" name="enlace" id="enlace" maxlength="255"
                           value="{{ old('enlace') }}" placeholder="https://...">
                </div>
            </div>
            <button class="btn" type="submit">Agregar al carrusel</button>
        </form>

        {{-- ---------- Las que ya estan ---------- --}}
        <h3 style="margin-top:26px;">Imagenes cargadas ({{ $imagenes->count() }})</h3>

        @if ($imagenes->isEmpty())
            <p class="carrusel-vacio">
                Todavia no hay imagenes. Mientras no haya ninguna publicada,
                el carrusel no aparece en la portada.
            </p>
        @else
            <div class="lista-carrusel">
                @foreach ($imagenes as $imagen)
                    <div class="fila-carrusel {{ $imagen->publicado ? '' : 'apagada' }}">
                        <img src="{{ $imagen->url }}" alt="{{ $imagen->titulo ?? 'Imagen del carrusel' }}">

                        <form action="{{ route('carrusel.update', $imagen) }}" method="POST" class="datos-carrusel">
                            @csrf
                            @method('PUT')

                            <div class="grid-4">
                                <div>
                                    <label>Titulo</label>
                                    <input type="text" name="titulo" maxlength="120" value="{{ $imagen->titulo }}">
                                </div>
                                <div>
                                    <label>Enlace</label>
                                    <input type="url" name="enlace" maxlength="255" value="{{ $imagen->enlace }}" placeholder="https://...">
                                </div>
                                <div>
                                    <label>Orden</label>
                                    <input type="number" name="orden" min="0" max="999" value="{{ $imagen->orden }}">
                                </div>
                                <div>
                                    <label>Estado</label>
                                    <label class="casilla">
                                        <input type="checkbox" name="publicado" value="1" @checked($imagen->publicado)>
                                        Publicada
                                    </label>
                                </div>
                            </div>

                            <div class="pie-carrusel">
                                <span class="nota">
                                    {{ $imagen->es_gif ? 'GIF (se publica sin comprimir)' : 'Imagen comprimida' }}
                                    @if ($imagen->peso_kb)
                                        &middot; {{ $imagen->peso_kb >= 1024 ? round($imagen->peso_kb / 1024, 1).' MB' : $imagen->peso_kb.' KB' }}
                                    @endif
                                </span>
                                <button class="btn btn-sm" type="submit">Guardar</button>
                            </div>
                        </form>

                        <form action="{{ route('carrusel.destroy', $imagen) }}" method="POST"
                              onsubmit="return confirm('¿Eliminar esta imagen del carrusel? Tambien se borra el archivo.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                        </form>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <style>
        .carrusel-sub { color: #666; font-size: 14px; margin-top: -8px; }
        .carrusel-vacio { color: #999; font-size: 14px; }
        .nota { color: #777; font-size: 12px; }

        .reglas {
            background: var(--gris-claro);
            border: 1px solid var(--gris-borde);
            border-left: 3px solid var(--azul-claro);
            border-radius: 6px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }
        .reglas ul { margin: 8px 0; padding-left: 20px; }
        .reglas li { font-size: 14px; margin-bottom: 4px; }
        .reglas > p { margin: 8px 0 0; }

        .subir input { margin-bottom: 0; }

        .lista-carrusel { display: grid; gap: 14px; }
        .fila-carrusel {
            display: grid;
            grid-template-columns: 180px 1fr auto;
            gap: 14px;
            align-items: start;
            border: 1px solid var(--gris-borde);
            border-radius: 8px;
            padding: 12px;
        }
        .fila-carrusel.apagada { opacity: .55; background: var(--gris-claro); }
        .fila-carrusel > img {
            width: 100%;
            aspect-ratio: 8 / 3;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid var(--gris-borde);
            background: var(--gris-claro);
        }
        .fila-carrusel input { margin-bottom: 0; }
        .casilla { display: flex; align-items: center; gap: 6px; font-weight: normal; }
        .pie-carrusel {
            display: flex; align-items: center; justify-content: space-between;
            gap: 10px; margin-top: 10px; flex-wrap: wrap;
        }

        @media (max-width: 760px) {
            .fila-carrusel { grid-template-columns: 1fr; }
        }
    </style>
@endsection
