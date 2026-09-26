<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Usuarios de desarrollo. La contraseña de ambos es "password".
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Oscar Poveda',
            'email' => 'oscar@gastos.test',
        ]);

        User::factory()->admin()->create([
            'name' => 'Administrador',
            'email' => 'admin@gastos.test',
        ]);

        // Cuentas, movimientos y presupuestos, para que la app abra con datos.
        $this->call(DatosDemoSeeder::class);
    }
}
