<?php

namespace Database\Factories;

use App\Enums\CategoriaMovimiento;
use App\Enums\TipoMovimiento;
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
            'tipo' => TipoMovimiento::Ingreso,
            'categoria' => fake()->randomElement(self::categoriasDe(TipoMovimiento::Ingreso)),
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
            'tipo' => TipoMovimiento::Gasto,
            'descripcion' => fake()->words(3, true),
            'monto' => fake()->randomFloat(2, 1000, 500000),
            'categoria' => fake()->randomElement(self::categoriasDe(TipoMovimiento::Gasto)),
            'fecha' => fake()->dateTimeBetween('-3 months')->format('Y-m-d'),
        ];
    }

    /**
     * Sale del enum y no de una lista escrita a mano: una factoría que genera
     * categorías que el servidor no acepta produce datos de prueba que no
     * podrían existir en producción, y esconde justo los fallos que buscas.
     *
     * @return array<int, string>
     */
    private static function categoriasDe(TipoMovimiento $tipo): array
    {
        return array_map(
            fn (CategoriaMovimiento $categoria) => $categoria->value,
            CategoriaMovimiento::de($tipo),
        );
    }
}
