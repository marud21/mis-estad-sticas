<?php

namespace App\Http\Requests;

use App\Models\NoticiaAdjunto;
use Illuminate\Foundation\Http\FormRequest;

class NoticiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'contenido' => ['required', 'string', 'max:10000'],
            'fecha_publicacion' => ['required', 'date'],
            'publicado' => ['sometimes', 'boolean'],
            'imagen' => ['nullable', 'image', 'max:15360'],
            'adjuntos' => ['sometimes', 'array', 'max:10'],
            'adjuntos.*' => ['file', 'mimes:'.implode(',', NoticiaAdjunto::EXTENSIONES), 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'adjuntos.max' => 'Se pueden adjuntar hasta 10 archivos por noticia.',
            'adjuntos.*.mimes' => 'Solo se permiten archivos '.implode(', ', NoticiaAdjunto::EXTENSIONES).'.',
            'adjuntos.*.max' => 'Cada archivo adjunto puede pesar como maximo 15 MB.',
        ];
    }

    /**
     * El servidor acepta hasta 20 MB por envio (post_max_size). Si se pasa,
     * PHP descarta la peticion entera antes de llegar aqui y el usuario no
     * entiende que paso, asi que se avisa antes con un limite propio y un
     * margen para el resto del formulario.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $archivos = array_filter((array) $this->file('adjuntos', []));

            if ($archivos === []) {
                return;
            }

            $totalBytes = array_sum(array_map(fn ($archivo) => $archivo->getSize(), $archivos));
            $limiteBytes = self::LIMITE_TOTAL_MB * 1024 * 1024;

            if ($totalBytes > $limiteBytes) {
                $validator->errors()->add('adjuntos', sprintf(
                    'Los archivos suman %s MB y entre todos no pueden pasar de %d MB en un mismo envio. Subelos en dos veces: puedes agregar mas adjuntos editando la noticia.',
                    number_format($totalBytes / (1024 * 1024), 1, ',', '.'),
                    self::LIMITE_TOTAL_MB,
                ));
            }
        });
    }

    /** Margen por debajo del post_max_size del servidor (20 MB). */
    public const LIMITE_TOTAL_MB = 18;
}
