<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_cargo', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('monto_default', 10, 2)->default(0);
            $table->boolean('es_recurrente')->default(false)->comment('true para mensualidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_cargo');
    }
};
