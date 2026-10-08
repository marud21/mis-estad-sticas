<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Archivos adjuntos de una noticia (documentos, planillas, imagenes).
     * Se guarda el nombre original para mostrarlo tal como lo subio quien
     * publico, y el tipo y peso para poder listarlos sin abrir el archivo.
     */
    public function up(): void
    {
        Schema::create('noticia_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('noticia_id')->constrained('noticias')->cascadeOnDelete();
            $table->string('nombre_original');
            $table->string('ruta');
            $table->string('tipo_mime')->nullable();
            $table->unsignedBigInteger('tamano')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('noticia_adjuntos');
    }
};
