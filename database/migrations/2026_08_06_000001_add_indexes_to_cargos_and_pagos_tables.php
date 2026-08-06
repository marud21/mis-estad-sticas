<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->index('fecha');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('cargos', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
        });
    }
};
