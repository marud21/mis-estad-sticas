<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sancion extends Model
{
    protected $table = 'sanciones';

    protected $fillable = ['socio_id', 'equipo_id', 'torneo_id', 'fechas', 'motivo', 'fecha_desde', 'user_id'];

    protected $casts = [
        'fechas' => 'integer',
        'fecha_desde' => 'date',
    ];

    public function socio(): BelongsTo
    {
        return $this->belongsTo(Socio::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Fechas que ya cumplio: los partidos que jugo su equipo desde que
     * empezo la sancion. Se cuenta solo, para no tener que ir marcandolo
     * a mano fecha por fecha.
     */
    public function getFechasCumplidasAttribute(): int
    {
        $jugados = Partido::computables()
            ->where('torneo_id', $this->torneo_id)
            ->whereDate('fecha_hora', '>=', $this->fecha_desde)
            ->where(fn ($q) => $q->where('equipo_local_id', $this->equipo_id)
                ->orWhere('equipo_visitante_id', $this->equipo_id))
            ->count();

        return min($jugados, $this->fechas);
    }

    public function getFechasRestantesAttribute(): int
    {
        return max(0, $this->fechas - $this->fechas_cumplidas);
    }

    public function getVigenteAttribute(): bool
    {
        return $this->fechas_restantes > 0;
    }

    /** Sanciones que todavia tienen fechas por cumplir. */
    public function scopeVigentes($query)
    {
        return $query->whereRaw('fechas > (
            SELECT COUNT(*) FROM partidos
            WHERE partidos.torneo_id = sanciones.torneo_id
              AND partidos.estado = ?
              AND partidos.goles_local IS NOT NULL
              AND partidos.goles_visitante IS NOT NULL
              AND DATE(partidos.fecha_hora) >= sanciones.fecha_desde
              AND (partidos.equipo_local_id = sanciones.equipo_id
                   OR partidos.equipo_visitante_id = sanciones.equipo_id)
        )', [Partido::ESTADO_JUGADO]);
    }
}
