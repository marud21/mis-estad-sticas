<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidenciaPartido extends Model
{
    protected $table = 'incidencias_partido';

    public const TIPO_GOL = 'gol';
    public const TIPO_AUTOGOL = 'autogol';
    public const TIPO_AMARILLA = 'tarjeta_amarilla';
    public const TIPO_ROJA = 'tarjeta_roja';

    /** Tipos de incidencia con su etiqueta. */
    public const TIPOS = [
        self::TIPO_GOL => 'Gol',
        self::TIPO_AUTOGOL => 'Autogol',
        self::TIPO_AMARILLA => 'Tarjeta amarilla',
        self::TIPO_ROJA => 'Tarjeta roja',
    ];

    /** Tarjetas y el tipo de cargo con el que se le cobran al socio. */
    public const TARJETAS = [
        self::TIPO_AMARILLA => 'Amarillas',
        self::TIPO_ROJA => 'Rojas',
    ];

    protected $fillable = ['partido_id', 'socio_id', 'equipo_id', 'tipo', 'cargo_id'];

    public function partido(): BelongsTo
    {
        return $this->belongsTo(Partido::class);
    }

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function getEsTarjetaAttribute(): bool
    {
        return array_key_exists($this->tipo, self::TARJETAS);
    }

    public function getEtiquetaAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
