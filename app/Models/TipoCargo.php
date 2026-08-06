<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCargo extends Model
{
    use HasFactory;

    protected $table = 'tipos_cargo';

    protected $fillable = ['nombre', 'monto_default', 'es_recurrente', 'porcentaje_suspendido'];

    protected $casts = [
        'monto_default' => 'decimal:2',
        'es_recurrente' => 'boolean',
        'porcentaje_suspendido' => 'decimal:2',
    ];

    public function cargos(): HasMany
    {
        return $this->hasMany(Cargo::class);
    }
}
