<?php

namespace Database\Seeders;

use App\Models\TipoCargo;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Administrador',
                'password' => env('ADMIN_PASSWORD', str()->random(16)),
                'role' => User::ROLE_ADMIN,
            ]
        );

        $tipos = [
            ['nombre' => 'Afiliacion', 'monto_default' => 50000, 'es_recurrente' => false],
            ['nombre' => 'Asamblea', 'monto_default' => 0, 'es_recurrente' => false],
            ['nombre' => 'Inscripcion', 'monto_default' => 30000, 'es_recurrente' => false],
            ['nombre' => 'Mensualidad', 'monto_default' => 20000, 'es_recurrente' => true],
            ['nombre' => 'Tarjeta', 'monto_default' => 15000, 'es_recurrente' => false],
            ['nombre' => 'Deuda anterior', 'monto_default' => 0, 'es_recurrente' => false],
        ];

        foreach ($tipos as $tipo) {
            TipoCargo::firstOrCreate(['nombre' => $tipo['nombre']], $tipo);
        }
    }
}
