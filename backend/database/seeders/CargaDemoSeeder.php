<?php

namespace Database\Seeders;

use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Genera un volumen realista de movimientos para medir el rendimiento del
 * dashboard. No forma parte del seeder por defecto: se ejecuta a mano con
 *
 *   php artisan db:seed --class=CargaDemoSeeder
 */
class CargaDemoSeeder extends Seeder
{
    private const CUANTOS = 10000;

    private const CATEGORIAS = [
        'alimentacion', 'transporte', 'vivienda', 'servicios',
        'salud', 'entretenimiento', 'compras', 'otros',
    ];

    public function run(): void
    {
        $usuario = User::firstOrCreate(
            ['email' => 'carga@gastos.test'],
            User::factory()->raw(['email' => 'carga@gastos.test', 'name' => 'Usuario de carga']),
        );

        Movimiento::withoutGlobalScopes()->where('user_id', $usuario->id)->delete();

        $inicio = now()->subYears(2);
        $filas = [];

        for ($i = 0; $i < self::CUANTOS; $i++) {
            $filas[] = [
                'user_id' => $usuario->id,
                'descripcion' => 'Movimiento de prueba '.$i,
                'monto' => random_int(1000, 500000) / 100,
                'categoria' => self::CATEGORIAS[array_rand(self::CATEGORIAS)],
                'fecha' => $inicio->copy()->addDays(random_int(0, 730))->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Insertar de mil en mil: una sola sentencia con 10 000 filas
            // revienta el límite de paquete de MySQL.
            if (count($filas) === 1000) {
                DB::table('movimientos')->insert($filas);
                $filas = [];
            }
        }

        if ($filas !== []) {
            DB::table('movimientos')->insert($filas);
        }

        $this->command->info(sprintf(
            '%d movimientos creados para %s (contraseña: password)',
            self::CUANTOS,
            $usuario->email,
        ));
    }
}
