<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Walkover: el equipo que no se presento.
     *
     * Basta una columna para saberlo todo: si esta llena, el partido se
     * gano por walkover y ahi esta el ausente; si esta vacia, se jugo
     * normal. No se agrega un estado nuevo al partido porque el walkover
     * sigue siendo un partido jugado y debe contar en la tabla igual que
     * los demas.
     *
     * Nace nula, asi que los 140 partidos que ya existen se quedan como
     * estan.
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->foreignId('equipo_ausente_id')
                ->nullable()
                ->after('jugador_partido_id')
                ->constrained('equipos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_ausente_id');
        });
    }
};
