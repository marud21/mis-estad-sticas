<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GastoCaja extends Model
{
    protected $table = 'gastos_caja';

    protected $fillable = ['cierre_caja_id', 'descripcion', 'monto'];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function cierreCaja(): BelongsTo
    {
        return $this->belongsTo(CierreCaja::class);
    }
}
