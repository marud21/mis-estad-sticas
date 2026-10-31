<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un navegador que acepto recibir avisos. No identifica a nadie: es la
 * direccion que el propio navegador entrega mas las claves con las que se
 * cifra el mensaje.
 */
class SuscripcionPush extends Model
{
    use HasFactory;

    protected $table = 'suscripciones_push';

    protected $fillable = ['endpoint', 'endpoint_huella', 'clave_p256dh', 'clave_auth', 'navegador'];

    /** Huella de la direccion, que es lo unico que se puede indexar. */
    public static function huella(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }
}
