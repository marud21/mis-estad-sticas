<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    protected $fillable = ['clave', 'valor'];

    public const WHATSAPP_CORPORACION = 'whatsapp_corporacion';
    public const NOMBRE_SISTEMA = 'nombre_sistema';
    public const LOGO_PATH = 'logo_path';
    public const DESCRIPCION_PORTADA = 'descripcion_portada';

    public const NOMBRE_SISTEMA_DEFECTO = 'Mis Estadisticas - Corvepatios';
    public const DESCRIPCION_PORTADA_DEFECTO = 'Sistema de administracion para la corporacion: registro de socios y equipos, control de cargos y pagos, y consulta de deuda en linea. Toda la gestion del club, en un solo lugar.';

    public static function obtener(string $clave, ?string $porDefecto = null): ?string
    {
        return static::where('clave', $clave)->value('valor') ?? $porDefecto;
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        static::updateOrCreate(['clave' => $clave], ['valor' => $valor]);
    }
}
