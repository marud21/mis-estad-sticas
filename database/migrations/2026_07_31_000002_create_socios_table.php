<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('socios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_completo');
            $table->string('numero_documento')->unique();
            $table->date('fecha_nacimiento');
            $table->string('entidad_salud');
            $table->string('celular');
            $table->string('tipo_sangre');
            $table->string('direccion_residencia');
            $table->string('posicion_juego');
            $table->unsignedTinyInteger('nivel_jugador')->comment('1=Bueno, 2=Regular, 3=Malo');
            $table->enum('estado', ['activo', 'suspendido', 'retirado'])->default('activo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('socios');
    }
};
