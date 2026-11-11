<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class CarruselImagen extends Model
{
    use HasFactory;

    protected $table = 'carrusel_imagenes';

    protected $fillable = ['titulo', 'imagen_path', 'enlace', 'orden', 'publicado', 'user_id'];

    protected $casts = [
        'publicado' => 'boolean',
        'orden' => 'integer',
    ];

    /** Las que se ven en la portada, en el orden elegido. */
    public function scopeVisibles($query)
    {
        return $query->where('publicado', true)->orderBy('orden')->orderBy('id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->imagen_path);
    }

    /**
     * Los GIF se guardan tal cual para no perder la animacion, asi que
     * conviene saber cuales son: son los unicos que pesan en el navegador
     * lo mismo que pesaban al subirlos.
     */
    public function getEsGifAttribute(): bool
    {
        return str_ends_with(strtolower($this->imagen_path), '.gif');
    }

    /** Peso del archivo guardado, en kilobytes. */
    public function getPesoKbAttribute(): ?int
    {
        $disco = Storage::disk('public');

        return $disco->exists($this->imagen_path)
            ? (int) round($disco->size($this->imagen_path) / 1024)
            : null;
    }
}
