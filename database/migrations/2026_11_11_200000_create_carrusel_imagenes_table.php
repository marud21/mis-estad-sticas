<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imagenes del carrusel de la portada.
     *
     * El orden se guarda a mano y no por fecha: quien publica decide que
     * va primero. Publicado permite dejar una imagen cargada sin que se
     * vea todavia, en vez de tener que borrarla y volverla a subir.
     */
    public function up(): void
    {
        Schema::create('carrusel_imagenes', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 120)->nullable()->comment('Texto alternativo y pie de la imagen');
            $table->string('imagen_path');
            $table->string('enlace', 255)->nullable()->comment('A donde lleva al tocarla, si aplica');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('publicado')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['publicado', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrusel_imagenes');
    }
};
