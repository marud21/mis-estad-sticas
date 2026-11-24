<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Noticia extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'contenido', 'imagen_path', 'fecha_publicacion', 'publicado', 'destacada', 'user_id'];

    protected $casts = [
        'fecha_publicacion' => 'date',
        'publicado' => 'boolean',
        'destacada' => 'boolean',
    ];

    /**
     * Cuantos dias sigue saltando una noticia en la portada.
     *
     * Pasado ese plazo deja de interrumpir, aunque siga siendo la ultima:
     * una ventana con una noticia de hace meses no avisa de nada y solo
     * estorba al que entra. La noticia no se va a ningun lado, sigue en
     * la seccion de Noticias.
     */
    public const DIAS_DESTACADA = 15;

    public function scopePublicadas($query)
    {
        return $query->where('publicado', true);
    }

    /** Las que se eligieron para que salten en la ventana de la portada. */
    public function scopeDestacadas($query)
    {
        return $query->where('destacada', true);
    }

    /**
     * La noticia que saltara en la ventana de la portada, si hay alguna.
     *
     * Tiene que estar publicada Y marcada para la ventana: no toda
     * noticia amerita interrumpir a quien entra. Entre las que cumplen,
     * manda la mas reciente.
     *
     * Se ordena igual que la lista publica (fecha y luego id) para que
     * "la ultima" signifique lo mismo en los dos lados: si dos noticias
     * salen el mismo dia, manda la que se creo despues.
     *
     * Las que tengan fecha futura no cuentan: si alguien se equivoca al
     * digitar el año, esa noticia se quedaria saltando para siempre.
     */
    public static function paraLaVentana(): ?self
    {
        $hoy = now()->startOfDay();

        return static::publicadas()
            ->destacadas()
            ->whereDate('fecha_publicacion', '<=', $hoy)
            ->whereDate('fecha_publicacion', '>=', $hoy->copy()->subDays(self::DIAS_DESTACADA))
            ->orderByDesc('fecha_publicacion')
            ->orderByDesc('id')
            ->first();
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adjuntos(): HasMany
    {
        return $this->hasMany(NoticiaAdjunto::class);
    }
}
