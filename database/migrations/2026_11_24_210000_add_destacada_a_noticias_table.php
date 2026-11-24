<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca para decidir que noticias saltan en la ventana de la portada.
 *
 * Antes saltaba siempre la ultima publicada. Ahora se elige: hay avisos
 * que vale la pena interrumpir con ellos y otros que no.
 *
 * Nace en "true" a proposito. Asi las noticias que ya existen se
 * comportan exactamente igual que hasta hoy —la ultima sigue saltando— y
 * nadie se encuentra con que el aviso que tenia publicado dejo de salir
 * despues de una actualizacion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('noticias', function (Blueprint $table) {
            $table->boolean('destacada')->default(true)->after('publicado');
        });
    }

    public function down(): void
    {
        Schema::table('noticias', function (Blueprint $table) {
            $table->dropColumn('destacada');
        });
    }
};
