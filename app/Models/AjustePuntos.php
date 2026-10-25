<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AjustePuntos extends Model
{
    protected $table = 'ajustes_puntos';

    protected $fillable = ['torneo_id', 'equipo_id', 'puntos', 'motivo', 'fecha', 'user_id'];

    protected $casts = [
        'puntos' => 'integer',
        'fecha' => 'date',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** "+3" o "-2", para mostrarlo siempre con su signo. */
    public function getPuntosConSignoAttribute(): string
    {
        return ($this->puntos > 0 ? '+' : '').$this->puntos;
    }
}
