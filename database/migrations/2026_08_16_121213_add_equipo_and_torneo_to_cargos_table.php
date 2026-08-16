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
        Schema::table('cargos', function (Blueprint $table) {
            $table->foreignId('equipo_id')->nullable()->after('socio_id')->constrained()->nullOnDelete();
            $table->foreignId('torneo_id')->nullable()->after('equipo_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('equipo_id');
            $table->dropConstrainedForeignId('torneo_id');
        });
    }
};
