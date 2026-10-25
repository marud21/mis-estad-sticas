<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Arbitro del partido. Es el unico dato que pedia la planilla de juego
     * y que el partido no tenia; guardarlo aqui permite generar la planilla
     * de un clic, sin volver a escribirlo cada vez.
     */
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->string('arbitro', 150)->nullable()->after('lugar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn('arbitro');
        });
    }
};
