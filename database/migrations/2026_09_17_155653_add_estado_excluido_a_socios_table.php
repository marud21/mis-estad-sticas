<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega el estado "excluido" al enum de socios. A un socio excluido
     * no se le generan cargos en los cobros masivos (mensualidad,
     * inscripcion, afiliacion), que solo alcanzan a activos y suspendidos.
     *
     * Se hace con SQL directo porque modificar un enum existente en MySQL
     * requiere redefinir la columna completa; no toca ningun dato: los
     * socios conservan el estado que ya tenian.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE socios MODIFY estado ENUM('activo', 'suspendido', 'retirado', 'excluido') NOT NULL DEFAULT 'activo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Los socios excluidos vuelven a "retirado", que es el estado sin
        // cobro mas cercano, para no perder el registro al revertir.
        DB::table('socios')->where('estado', 'excluido')->update(['estado' => 'retirado']);

        DB::statement("ALTER TABLE socios MODIFY estado ENUM('activo', 'suspendido', 'retirado') NOT NULL DEFAULT 'activo'");
    }
};
