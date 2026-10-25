<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Partido extends Model
{
    use HasFactory;

    public const ESTADO_PROGRAMADO = 'programado';
    public const ESTADO_JUGADO = 'jugado';
    public const ESTADO_APLAZADO = 'aplazado';
    public const ESTADO_SUSPENDIDO = 'suspendido';
    public const ESTADO_CANCELADO = 'cancelado';

    /** Estados posibles, con la etiqueta que se muestra en pantalla. */
    public const ESTADOS = [
        self::ESTADO_PROGRAMADO => 'Programado',
        self::ESTADO_JUGADO => 'Jugado',
        self::ESTADO_APLAZADO => 'Aplazado',
        self::ESTADO_SUSPENDIDO => 'Suspendido',
        self::ESTADO_CANCELADO => 'Cancelado',
    ];

    protected $fillable = [
        'torneo_id',
        'equipo_local_id',
        'equipo_visitante_id',
        'goles_local',
        'goles_visitante',
        'fecha_hora',
        'lugar',
        'arbitro',
        'fase',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_hora' => 'datetime',
        'goles_local' => 'integer',
        'goles_visitante' => 'integer',
    ];

    public function torneo(): BelongsTo
    {
        return $this->belongsTo(Torneo::class);
    }

    public function equipoLocal(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_local_id');
    }

    public function equipoVisitante(): BelongsTo
    {
        return $this->belongsTo(Equipo::class, 'equipo_visitante_id');
    }

    public function incidencias(): HasMany
    {
        return $this->hasMany(IncidenciaPartido::class);
    }

    /** Goles y tarjetas de un equipo en este partido, por socio. */
    public function incidenciasDe(int $equipoId)
    {
        return $this->incidencias->where('equipo_id', $equipoId);
    }

    /**
     * Solo los partidos jugados y con marcador cargado cuentan para la
     * tabla de posiciones: un partido aplazado o sin resultado no suma.
     */
    public function scopeComputables($query)
    {
        return $query->where('estado', self::ESTADO_JUGADO)
            ->whereNotNull('goles_local')
            ->whereNotNull('goles_visitante');
    }

    public function getTieneResultadoAttribute(): bool
    {
        return $this->goles_local !== null && $this->goles_visitante !== null;
    }

    public function getMarcadorAttribute(): string
    {
        return $this->tiene_resultado
            ? "{$this->goles_local} - {$this->goles_visitante}"
            : 'vs';
    }

    public function getEstadoEtiquetaAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst($this->estado);
    }
}
