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

    /**
     * Socio excluido: no se le generan cargos en los cobros masivos
     * (mensualidad, inscripcion, afiliacion). Conserva la deuda que ya
     * tuviera; simplemente deja de acumular cargos nuevos.
     */
    public const ESTADO_EXCLUIDO = 'excluido';

    /** Todos los estados posibles de un socio, para filtros y selectores. */
    public const ESTADOS = [
        self::ESTADO_ACTIVO,
        self::ESTADO_SUSPENDIDO,
        self::ESTADO_RETIRADO,
        self::ESTADO_EXCLUIDO,
    ];

    public const CARNET_TIENE = 'tiene';
    public const CARNET_EXTRAVIADO = 'extraviado';
    public const CARNET_NO_TIENE = 'no_tiene';

    /** Estados del carnet, con la etiqueta que se muestra en pantalla. */
    public const CARNETS = [
        self::CARNET_TIENE => 'Tiene',
        self::CARNET_EXTRAVIADO => 'Extraviado',
        self::CARNET_NO_TIENE => 'No tiene',
    ];

    /** Simbolo de cada estado, para leer la columna de un vistazo. */
    public const CARNET_SIMBOLOS = [
        self::CARNET_TIENE => '✅',
        self::CARNET_EXTRAVIADO => '➖',
        self::CARNET_NO_TIENE => '❌',
    ];

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
        'carnet',
        'fecha_cambio_estado',
        'suspendido_por_equipo',
        'cuota_moderada',
        'cuota_moderada_fecha',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'nivel_jugador' => 'integer',
        'fecha_cambio_estado' => 'date',
        'suspendido_por_equipo' => 'boolean',
        'cuota_moderada' => 'decimal:2',
        'cuota_moderada_fecha' => 'date',
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

    /** Etiqueta legible del estado del carnet ("Tiene", "Extraviado", ...). */
    public function getCarnetEtiquetaAttribute(): string
    {
        return self::CARNETS[$this->carnet] ?? self::CARNETS[self::CARNET_NO_TIENE];
    }

    /** Simbolo del estado del carnet, para las listas. */
    public function getCarnetSimboloAttribute(): string
    {
        return self::CARNET_SIMBOLOS[$this->carnet] ?? self::CARNET_SIMBOLOS[self::CARNET_NO_TIENE];
    }

    /**
     * Desglosa cargos, pagos y deuda por cada equipo, usando las relaciones
     * cargos/pagos ya cargadas (no vuelve a consultar la BD). Se agrupa por
     * el equipo_id que quedo grabado en cada cargo/pago (no por los equipos
     * actuales del socio), para que un cargo de un equipo del que luego se
     * lo quito siga apareciendo aqui y la suma de las filas siempre cuadre
     * con el total general. Los cargos/pagos sin equipo asociado se agrupan
     * en un renglon "General / sin equipo".
     */
    public function getDeudaPorEquipoAttribute(): \Illuminate\Support\Collection
    {
        $idsEquipo = $this->cargos->pluck('equipo_id')
            ->merge($this->pagos->pluck('equipo_id'))
            ->unique();

        return $idsEquipo
            ->map(function ($equipoId) {
                $totalCargos = (float) $this->cargos->where('equipo_id', $equipoId)->sum('monto');
                $totalPagos = (float) $this->pagos->where('equipo_id', $equipoId)->sum('valor');

                $nombreEquipo = $equipoId
                    ? ($this->cargos->firstWhere('equipo_id', $equipoId)?->equipo?->nombre
                        ?? $this->pagos->firstWhere('equipo_id', $equipoId)?->equipo?->nombre
                        ?? 'Equipo eliminado')
                    : 'General / sin equipo';

                return (object) [
                    'equipo' => $nombreEquipo,
                    'total_cargos' => $totalCargos,
                    'total_pagos' => $totalPagos,
                    'deuda' => $totalCargos - $totalPagos,
                ];
            })
            ->sortBy(fn ($fila) => $fila->equipo === 'General / sin equipo' ? 1 : 0)
            ->values();
    }

    /**
     * Cuota moderada (abono minimo sugerido) vigente: usa el valor fijado
     * en el ultimo recalculo manual (no se recalcula solo en cada pago),
     * pero nunca sugiere pagar mas de lo que realmente se debe. Si aun no
     * se ha calculado nunca, cae al porcentaje configurado (por defecto
     * 25%) de la deuda actual.
     */
    public function getCuotaModeradaVigenteAttribute(): float
    {
        $deuda = $this->deuda_total;

        if ($deuda <= 0) {
            return 0;
        }

        if ($this->cuota_moderada !== null) {
            $base = (float) $this->cuota_moderada;
        } else {
            $porcentaje = (float) Configuracion::obtener(Configuracion::PORCENTAJE_CUOTA_MODERADA, Configuracion::PORCENTAJE_CUOTA_MODERADA_DEFECTO);
            $base = round($deuda * ($porcentaje / 100), 2);
        }

        return min($base, $deuda);
    }
}
