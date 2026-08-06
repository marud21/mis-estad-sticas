<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cargo extends Model
{
    use HasFactory;

    protected $fillable = ['socio_id', 'tipo_cargo_id', 'monto', 'fecha', 'descripcion'];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function tipoCargo(): BelongsTo
    {
        return $this->belongsTo(TipoCargo::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function getSaldoAttribute(): float
    {
        return (float) $this->monto - (float) $this->pagos()->sum('valor');
    }
}
