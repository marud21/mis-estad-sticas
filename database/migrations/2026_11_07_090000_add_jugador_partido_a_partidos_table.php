<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El jugador del partido: la figura que elige quien registra el
     * resultado. Es opcional, asi que la columna nace nula y los partidos
     * que ya estaban cargados se quedan como estan, sin figura.
     *
     * Si el socio se borra, el partido conserva todo lo demas y solo
     * queda sin figura: perder un resultado por eso no tendria sentido.
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->foreignId('jugador_partido_id')
                ->nullable()
                ->after('arbitro')
                ->constrained('socios')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('jugador_partido_id');
        });
    }
};
