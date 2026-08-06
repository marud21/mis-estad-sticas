<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    public const TIPO_EFECTIVO = 'efectivo';
    public const TIPO_TRANSFERENCIA = 'transferencia';

    protected $fillable = ['socio_id', 'cargo_id', 'equipo_id', 'valor', 'fecha', 'tipo'];

    protected $casts = [
        'valor' => 'decimal:2',
        'fecha' => 'date',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}
