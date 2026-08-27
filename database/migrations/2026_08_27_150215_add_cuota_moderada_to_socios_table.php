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
            $table->decimal('cuota_moderada', 12, 2)->nullable()->after('suspendido_por_equipo');
            $table->date('cuota_moderada_fecha')->nullable()->after('cuota_moderada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('socios', function (Blueprint $table) {
            $table->dropColumn(['cuota_moderada', 'cuota_moderada_fecha']);
        });
    }
};
