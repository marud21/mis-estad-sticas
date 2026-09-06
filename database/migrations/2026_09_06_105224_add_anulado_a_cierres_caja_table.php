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
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropUnique('cierres_caja_fecha_unique');

            $table->boolean('anulado')->default(false)->after('user_id');
            $table->text('anulado_motivo')->nullable()->after('anulado');
            $table->foreignId('anulado_por')->nullable()->after('anulado_motivo')->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_en')->nullable()->after('anulado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anulado_por');
            $table->dropColumn(['anulado', 'anulado_motivo', 'anulado_en']);

            $table->unique('fecha');
        });
    }
};
