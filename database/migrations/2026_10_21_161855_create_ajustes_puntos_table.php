<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Puntos que se le suman o restan a un equipo por fuera de los
     * partidos: sanciones del comite, bonificaciones o cualquier regla del
     * torneo. Cada ajuste guarda su motivo para que la tabla de posiciones
     * siempre pueda explicarse.
     *
     * El valor es con signo: negativo descuenta, positivo bonifica.
     */
    public function up(): void
    {
        Schema::create('ajustes_puntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->smallInteger('puntos');
            $table->string('motivo', 255);
            $table->date('fecha');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['torneo_id', 'equipo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes_puntos');
    }
};
