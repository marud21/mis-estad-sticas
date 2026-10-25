<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lo que paso en un partido, jugador por jugador: goles, autogoles y
     * tarjetas. De aqui sale el marcador y, en el caso de las tarjetas, el
     * cargo que se le genera al socio.
     *
     * cargo_id enlaza la tarjeta con el cargo que se creo, para poder
     * retirarlo si luego se corrige el resultado. Si el cargo se borra por
     * otro lado, la incidencia se conserva y solo queda sin enlace.
     */
    public function up(): void
    {
        Schema::create('incidencias_partido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->foreignId('socio_id')->constrained('socios')->restrictOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->restrictOnDelete();
            $table->enum('tipo', ['gol', 'autogol', 'tarjeta_amarilla', 'tarjeta_roja']);
            $table->foreignId('cargo_id')->nullable()->constrained('cargos')->nullOnDelete();
            $table->timestamps();

            $table->index(['partido_id', 'tipo']);
            $table->index(['socio_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidencias_partido');
    }
};
