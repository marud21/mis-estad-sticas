<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado del carnet de cada socio, para llevar el control de quienes lo
     * tienen, a quienes se les extravio y a quienes falta entregarselo.
     * Los socios que ya existen quedan en "no_tiene", que es el punto de
     * partida correcto mientras no se registre lo contrario.
     */
    public function up(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->enum('carnet', ['tiene', 'extraviado', 'no_tiene'])
                ->default('no_tiene')
                ->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->dropColumn('carnet');
        });
    }
};
