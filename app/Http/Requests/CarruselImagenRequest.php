<?php

namespace App\Http\Requests;

use App\Services\CarruselService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CarruselImagenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen' => [
                'required',
                'image',
                'mimes:'.implode(',', CarruselService::FORMATOS),
                // El tope general. Al GIF se le exige menos, y eso se
                // comprueba aparte porque depende del formato.
                'max:'.CarruselService::PESO_MAXIMO_KB,
                'dimensions:min_width='.CarruselService::ANCHO_MINIMO,
            ],
            'titulo' => ['nullable', 'string', 'max:120'],
            'enlace' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $archivo = $this->file('imagen');

            if (! $archivo || ! $archivo->isValid()) {
                return;
            }

            $esGif = strtolower((string) $archivo->getClientOriginalExtension()) === 'gif'
                || $archivo->getMimeType() === 'image/gif';

            // El GIF se guarda sin comprimir para no perder la animacion,
            // asi que su peso es el que descarga cada visitante.
            if ($esGif && $archivo->getSize() > CarruselService::PESO_MAXIMO_GIF_KB * 1024) {
                $v->errors()->add('imagen', sprintf(
                    'El GIF no puede pesar mas de %s MB, porque se publica tal cual y es lo que descarga cada visitante. Este pesa %s MB.',
                    round(CarruselService::PESO_MAXIMO_GIF_KB / 1024),
                    round($archivo->getSize() / 1048576, 1),
                ));
            }
        });
    }

    public function messages(): array
    {
        $r = CarruselService::reglas();

        return [
            'imagen.required' => 'Elige una imagen para subir.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'Solo se aceptan estos formatos: '.$r['formatos'].'.',
            'imagen.max' => 'La imagen no puede pesar mas de '.$r['peso_estatica'].'.',
            'imagen.dimensions' => 'La imagen debe tener al menos '.$r['ancho_minimo'].'. Mas angosta se ve borrosa en pantallas grandes.',
            'enlace.url' => 'El enlace debe ser una direccion completa, por ejemplo https://...',
        ];
    }
}
