<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $fillable = [
        'fecha',
        'total_efectivo',
        'total_transferencia',
        'total_ingresos',
        'total_gastos',
        'total_neto_efectivo',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total_efectivo' => 'decimal:2',
        'total_transferencia' => 'decimal:2',
        'total_ingresos' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_neto_efectivo' => 'decimal:2',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(GastoCaja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
