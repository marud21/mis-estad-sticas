<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Navegadores que aceptaron recibir avisos. No se pide cuenta ni datos
     * personales: el navegador entrega una direccion propia y dos claves
     * con las que se cifra el mensaje, y eso es todo lo que se guarda.
     *
     * La direccion es larga y varia entre navegadores, asi que lo unico
     * que se indexa es su huella: una cadena de largo fijo que sirve para
     * no guardar dos veces la misma suscripcion.
     */
    public function up(): void
    {
        Schema::create('suscripciones_push', function (Blueprint $table) {
            $table->id();
            $table->text('endpoint');
            $table->char('endpoint_huella', 64)->unique();
            $table->string('clave_p256dh', 255);
            $table->string('clave_auth', 255);
            $table->string('navegador', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suscripciones_push');
    }
};
