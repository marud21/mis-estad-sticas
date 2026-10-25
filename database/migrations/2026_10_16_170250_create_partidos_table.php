<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Partidos de un torneo: la programacion del calendario y, una vez
     * jugados, el marcador con el que se arma la tabla de posiciones.
     * Los equipos se restringen en vez de borrarse en cascada para no
     * perder el historial de un partido si se elimina un equipo.
     */
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('torneo_id')->constrained('torneos')->cascadeOnDelete();
            $table->foreignId('equipo_local_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('equipo_visitante_id')->constrained('equipos')->restrictOnDelete();
            $table->unsignedTinyInteger('goles_local')->nullable();
            $table->unsignedTinyInteger('goles_visitante')->nullable();
            $table->dateTime('fecha_hora');
            $table->string('lugar', 150)->nullable();
            $table->string('fase', 50)->nullable()->comment('Jornada, fecha o fase: "Fecha 3", "Semifinal"');
            $table->enum('estado', ['programado', 'jugado', 'aplazado', 'suspendido', 'cancelado'])
                ->default('programado');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['torneo_id', 'estado']);
            $table->index('fecha_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};
