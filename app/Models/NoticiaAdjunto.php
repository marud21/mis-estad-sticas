<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class NoticiaAdjunto extends Model
{
    protected $table = 'noticia_adjuntos';

    protected $fillable = ['noticia_id', 'nombre_original', 'ruta', 'tipo_mime', 'tamano'];

    protected $casts = [
        'tamano' => 'integer',
    ];

    /**
     * Extensiones que se aceptan al subir un adjunto. Incluye los formatos
     * modernos de Word y de foto, porque son los que usan los dispositivos
     * hoy en dia y de otro modo se rechazarian.
     */
    public const EXTENSIONES = ['doc', 'docx', 'xml', 'pdf', 'png', 'jpg', 'jpeg'];

    /** Lo que el navegador puede mostrar sin descargar el archivo. */
    public const EXTENSIONES_IMAGEN = ['png', 'jpg', 'jpeg'];

    public const EXTENSIONES_PREVISUALIZABLES = ['png', 'jpg', 'jpeg', 'pdf', 'xml'];

    /**
     * Disco donde viven los adjuntos.
     *
     * Es el privado, no el publico. En el publico cada archivo queda con
     * una direccion fija bajo /storage y cualquiera que la vea se lo
     * lleva, aunque la pagina no ofrezca ningun boton de descarga. Aqui
     * no hay direccion directa: el unico camino es la ruta que los
     * entrega, que comprueba antes que la noticia este publicada.
     */
    public const DISCO = 'local';

    public function noticia(): BelongsTo
    {
        return $this->belongsTo(Noticia::class);
    }

    /** Por donde se ve el adjunto. No es el archivo: es la ruta que lo entrega. */
    public function getUrlAttribute(): string
    {
        return route('noticias-publicas.adjunto', [$this->noticia_id, $this->id]);
    }

    /**
     * Lo que se le dice al navegador que es.
     *
     * No se usa el tipo que mando el navegador al subirlo: ese lo elige
     * quien sube y no hay por que creerle. Se deduce de la extension, que
     * es la que ya se valido al aceptar el archivo.
     */
    public function getTipoServidoAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'xml' => 'text/plain; charset=UTF-8',
            default => 'application/octet-stream',
        };
    }

    public function existeElArchivo(): bool
    {
        return Storage::disk(self::DISCO)->exists($this->ruta);
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->nombre_original, PATHINFO_EXTENSION));
    }

    public function getEsImagenAttribute(): bool
    {
        return in_array($this->extension, self::EXTENSIONES_IMAGEN, true);
    }

    public function getEsPdfAttribute(): bool
    {
        return $this->extension === 'pdf';
    }

    /**
     * Un .doc o .docx no se puede mostrar dentro del navegador: de esos
     * solo se ofrece la descarga.
     */
    public function getSePuedePrevisualizarAttribute(): bool
    {
        return in_array($this->extension, self::EXTENSIONES_PREVISUALIZABLES, true);
    }

    /** Peso del archivo en un formato legible ("1,2 MB"). */
    public function getTamanoLegibleAttribute(): string
    {
        $bytes = $this->tamano;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 0, ',', '.').' KB';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', '.').' MB';
    }

    /** Icono segun el tipo de archivo, para reconocerlo de un vistazo. */
    public function getIconoAttribute(): string
    {
        return match ($this->extension) {
            'pdf' => '📕',
            'doc', 'docx' => '📘',
            'xml' => '📑',
            'png', 'jpg', 'jpeg' => '🖼️',
            default => '📎',
        };
    }
}
