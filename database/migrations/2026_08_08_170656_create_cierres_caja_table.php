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
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->unique();
            $table->decimal('total_efectivo', 12, 2)->default(0);
            $table->decimal('total_transferencia', 12, 2)->default(0);
            $table->decimal('total_ingresos', 12, 2)->default(0);
            $table->decimal('total_gastos', 12, 2)->default(0);
            $table->decimal('total_neto_efectivo', 12, 2)->default(0);
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
