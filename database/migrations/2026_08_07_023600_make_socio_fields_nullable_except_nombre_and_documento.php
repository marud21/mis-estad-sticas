<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->string('entidad_salud')->nullable()->change();
            $table->string('celular')->nullable()->change();
            $table->string('tipo_sangre')->nullable()->change();
            $table->string('direccion_residencia')->nullable()->change();
            $table->string('posicion_juego')->nullable()->change();
            $table->unsignedTinyInteger('nivel_jugador')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->string('entidad_salud')->nullable(false)->change();
            $table->string('celular')->nullable(false)->change();
            $table->string('tipo_sangre')->nullable(false)->change();
            $table->string('direccion_residencia')->nullable(false)->change();
            $table->string('posicion_juego')->nullable(false)->change();
            $table->unsignedTinyInteger('nivel_jugador')->nullable(false)->change();
        });
    }
};
