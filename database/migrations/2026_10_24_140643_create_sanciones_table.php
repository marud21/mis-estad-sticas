<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sanciones que impone la corporacion a un jugador por una cantidad de
     * fechas. Es distinto de una tarjeta: la tarjeta es lo que paso en un
     * partido, la sancion es la pena que se le impone despues.
     *
     * Las fechas cumplidas no se llevan a mano: se cuentan los partidos que
     * jugo su equipo desde que empezo la sancion.
     */
    public function up(): void
    {
        Schema::create('sanciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained('socios')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->unsignedTinyInteger('fechas');
            $table->string('motivo', 255);
            $table->date('fecha_desde');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['torneo_id', 'socio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sanciones');
    }
};
