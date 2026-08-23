<?php

namespace App\Http\Requests;

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
        ];
    }
}
