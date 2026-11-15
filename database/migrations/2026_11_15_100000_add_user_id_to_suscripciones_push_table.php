<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca cuales suscripciones son de alguien de la administracion.
     *
     * Hasta ahora todos los avisos eran de resultados y se mandaban a
     * todo el que los hubiera aceptado, que es cualquiera que entre a la
     * pagina publica. El aviso de socios sin pagar no puede ir ahi: dice
     * cuanta gente debe y cuanto. Guardar quien activo el aviso permite
     * mandar esos avisos solo a los navegadores de quien tiene sesion.
     *
     * La columna admite nulo y nace vacia: las suscripciones que ya
     * existen quedan como estan y siguen recibiendo los resultados
     * exactamente igual que antes.
     */
    public function up(): void
    {
        Schema::table('suscripciones_push', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('navegador')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('suscripciones_push', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
