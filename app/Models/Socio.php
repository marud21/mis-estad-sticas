<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Socio extends Model
{
    use HasFactory;

    public const ESTADO_ACTIVO = 'activo';
    public const ESTADO_SUSPENDIDO = 'suspendido';
    public const ESTADO_RETIRADO = 'retirado';

    public const NIVEL_BUENO = 1;
    public const NIVEL_REGULAR = 2;
    public const NIVEL_MALO = 3;

    protected $fillable = [
        'nombre_completo',
        'numero_documento',
        'fecha_nacimiento',
        'fecha_ingreso',
        'entidad_salud',
        'celular',
        'tipo_sangre',
        'direccion_residencia',
        'posicion_juego',
        'numero_camiseta',
        'foto_path',
        'nivel_jugador',
        'estado',
        'fecha_cambio_estado',
        'suspendido_por_equipo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'nivel_jugador' => 'integer',
        'fecha_cambio_estado' => 'date',
        'suspendido_por_equipo' => 'boolean',
    ];

    public function equipos(): BelongsToMany
    {
        return $this->belongsToMany(Equipo::class, 'equipo_socio');
    }

    public function equipoActual(): ?Equipo
    {
        return $this->equipos->first();
    }

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function getTotalCargosAttribute(): float
    {
        return (float) $this->cargos()->sum('monto');
    }

    public function getTotalPagosAttribute(): float
    {
        return (float) $this->pagos()->sum('valor');
    }

    public function getDeudaTotalAttribute(): float
    {
        return $this->total_cargos - $this->total_pagos;
    }
}
