<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipo extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_INACTIVO = 'inactivo';

    protected $fillable = ['nombre', 'categoria', 'descripcion', 'torneo_id', 'estado', 'fecha_cambio_estado'];

    protected $casts = [
        'fecha_cambio_estado' => 'date',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function socios(): BelongsToMany
    {
        return $this->belongsToMany(Socio::class, 'equipo_socio');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }
}
