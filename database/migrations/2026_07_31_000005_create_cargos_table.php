<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('socio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tipo_cargo_id')->constrained('tipos_cargo')->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
