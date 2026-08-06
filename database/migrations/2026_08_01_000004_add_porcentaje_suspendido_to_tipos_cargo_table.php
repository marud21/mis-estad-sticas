<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_cargo', function (Blueprint $table) {
            $table->decimal('porcentaje_suspendido', 5, 2)->default(25.00)->after('es_recurrente')
                ->comment('Porcentaje del monto que se cobra a socios suspendidos en la carga masiva');
        });
    }

    public function down(): void
    {
        Schema::table('tipos_cargo', function (Blueprint $table) {
            $table->dropColumn('porcentaje_suspendido');
        });
    }
};
