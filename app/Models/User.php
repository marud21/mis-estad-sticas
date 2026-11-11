<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /** Acceso completo al sistema. */
    public const ROLE_ADMIN = 'admin';

    /**
     * Directivo: finanzas (sin reportes contables), sistema y noticias.
     * Socios y equipos los ve pero no los modifica, y no entra al panel.
     * El detalle de que puede abrir esta en App\Support\Permisos.
     */
    public const ROLE_DIRECTIVO = 'directivo';

    /** Los roles con su nombre en pantalla. */
    public const ROLES = [
        self::ROLE_ADMIN => 'Administrador',
        self::ROLE_DIRECTIVO => 'Directivo',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function esDirectivo(): bool
    {
        return $this->role === self::ROLE_DIRECTIVO;
    }

    /** Nombre del rol tal como se muestra en pantalla. */
    public function getRolEtiquetaAttribute(): string
    {
        return self::ROLES[$this->role] ?? ucfirst((string) $this->role);
    }

    /**
     * A donde llega al entrar. El directivo no tiene panel, asi que
     * empieza en la lista de socios.
     */
    public function pantallaDeInicio(): string
    {
        return $this->esDirectivo() ? route('socios.index') : route('panel.index');
    }
}
