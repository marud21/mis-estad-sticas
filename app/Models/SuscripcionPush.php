<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un navegador que acepto recibir avisos. No identifica a nadie: es la
 * direccion que el propio navegador entrega mas las claves con las que se
 * cifra el mensaje.
 */
class SuscripcionPush extends Model
{
    use HasFactory;

    protected $table = 'suscripciones_push';

    protected $fillable = ['endpoint', 'endpoint_huella', 'clave_p256dh', 'clave_auth', 'navegador', 'user_id'];

    /**
     * Quien activo el aviso, si lo hizo con la sesion abierta.
     *
     * Queda en nulo cuando lo activa un visitante de la pagina publica,
     * que es lo normal para los avisos de resultados.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Solo las de alguien de la administracion. */
    public function scopeDeLaAdministracion($query)
    {
        return $query->whereNotNull('user_id');
    }

    /** Huella de la direccion, que es lo unico que se puede indexar. */
    public static function huella(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}
