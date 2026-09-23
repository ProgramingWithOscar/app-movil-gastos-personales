<?php

namespace Database\Factories;

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimiento>
 */
class MovimientoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function ingreso(): static
    {
        return $this->state(fn () => [
            'tipo' => 'ingreso',
            'categoria' => fake()->randomElement(['salario', 'freelance', 'negocio', 'inversiones', 'otros']),
        ]);
    }

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            // Un movimiento sin cuenta ya no existe. Se crea una para el mismo
            // dueño en vez de una suelta: si no, el movimiento pertenecería a
            // un usuario y su cuenta a otro.
            'cuenta_id' => fn (array $atributos) => Cuenta::factory()->create([
                'user_id' => $atributos['user_id'],
            ])->id,
            'tipo' => 'gasto',
            'descripcion' => fake()->words(3, true),
            'monto' => fake()->randomFloat(2, 1000, 500000),
            'categoria' => fake()->randomElement(['alimentacion', 'transporte', 'servicios', 'salud', 'otros']),
            'fecha' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
        ];
    }
}
