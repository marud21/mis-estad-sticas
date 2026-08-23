<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Noticia extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'contenido', 'imagen_path', 'fecha_publicacion', 'publicado', 'user_id'];

    protected $casts = [
        'fecha_publicacion' => 'date',
        'publicado' => 'boolean',
    ];

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
