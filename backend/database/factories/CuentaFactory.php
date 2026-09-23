<?php

namespace Database\Factories;

use App\Enums\TipoCuenta;
use App\Models\Cuenta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cuenta>
 */
class CuentaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nombre' => fake()->unique()->words(2, true),
            'tipo' => TipoCuenta::Efectivo,
            'moneda' => 'COP',
            'saldo_inicial' => fake()->randomFloat(2, 0, 1000000),
            'favorita' => false,
            'archivada' => false,
        ];
    }

    public function archivada(): static
    {
        return $this->state(fn () => ['archivada' => true]);
    }

    public function favorita(): static
    {
        return $this->state(fn () => ['favorita' => true]);
    }

    /** Arranca con deuda: es lo normal en una tarjeta. */
    public function tarjetaCredito(): static
    {
        return $this->state(fn () => [
            'tipo' => TipoCuenta::TarjetaCredito,
            'saldo_inicial' => -fake()->randomFloat(2, 100000, 2000000),
        ]);
    }
}
