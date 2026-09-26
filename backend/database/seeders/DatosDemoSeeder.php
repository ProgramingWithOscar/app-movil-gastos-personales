<?php

namespace Database\Seeders;

use App\Enums\CategoriaMovimiento;
use App\Enums\TipoCuenta;
use App\Enums\TipoMovimiento;
use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\Presupuesto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Cuentas, movimientos y presupuestos para tener la app con datos al abrirla.
 *
 * Existe porque estos datos se habían creado a mano, uno a uno, y al vaciarse
 * la base de desarrollo no hubo forma de recuperarlos. Un dato de trabajo que
 * solo vive en la base es un dato que se pierde entero la primera vez que algo
 * la trunca.
 *
 * Forma parte del seeder por defecto. Para el volumen de medir rendimiento
 * está CargaDemoSeeder aparte, que son 10 000 filas y tarda.
 */
class DatosDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (User::all() as $usuario) {
            $this->paraUsuario($usuario);
        }
    }

    private function paraUsuario(User $usuario): void
    {
        $bancolombia = $this->cuenta($usuario, 'Bancolombia', TipoCuenta::Bancaria, 3200000, true);
        $efectivo = $this->cuenta($usuario, 'Efectivo', TipoCuenta::Efectivo, 250000);
        $visa = $this->cuenta($usuario, 'Visa Oro', TipoCuenta::TarjetaCredito, 0);

        // Tres meses hacia atrás, para que la comparación con el período
        // anterior del dashboard tenga con qué comparar.
        foreach ([2, 1, 0] as $atras) {
            $mes = now()->subMonths($atras);

            $this->movimiento($bancolombia, TipoMovimiento::Ingreso, CategoriaMovimiento::Salario,
                'Nómina', 3200000, $mes->copy()->day(min(5, $mes->daysInMonth)));

            if ($atras !== 2) {
                $this->movimiento($bancolombia, TipoMovimiento::Ingreso, CategoriaMovimiento::TrabajoIndependiente,
                    'Proyecto freelance', 850000, $mes->copy()->day(min(18, $mes->daysInMonth)));
            }

            $gastos = [
                [CategoriaMovimiento::Vivienda, 'Arriendo', 1200000, 3, $bancolombia],
                [CategoriaMovimiento::Servicios, 'Servicios públicos', 185000, 8, $bancolombia],
                [CategoriaMovimiento::Alimentacion, 'Mercado', 420000, 10, $bancolombia],
                [CategoriaMovimiento::Alimentacion, 'Almuerzos', 95000, 14, $efectivo],
                [CategoriaMovimiento::Transporte, 'Gasolina', 160000, 12, $visa],
                [CategoriaMovimiento::Entretenimiento, 'Streaming', 45000, 20, $visa],
                [CategoriaMovimiento::Salud, 'Farmacia', 78000, 22, $efectivo],
                [CategoriaMovimiento::Compras, 'Ropa', 230000, 25, $visa],
            ];

            foreach ($gastos as [$categoria, $descripcion, $monto, $dia, $cuenta]) {
                $this->movimiento($cuenta, TipoMovimiento::Gasto, $categoria, $descripcion,
                    $monto, $mes->copy()->day(min($dia, $mes->daysInMonth)));
            }
        }

        // Uno general y uno por categoría, que es la combinación que ejercita
        // los dos caminos del evaluador.
        Presupuesto::firstOrCreate(
            ['user_id' => $usuario->id, 'categoria' => null],
            ['monto' => 2800000, 'periodo' => 'mes', 'umbral_alerta' => 80, 'activo' => true],
        );

        Presupuesto::firstOrCreate(
            ['user_id' => $usuario->id, 'categoria' => CategoriaMovimiento::Vivienda->value],
            ['monto' => 1300000, 'periodo' => 'mes', 'umbral_alerta' => 90, 'activo' => true],
        );
    }

    private function cuenta(User $usuario, string $nombre, TipoCuenta $tipo, float $saldo, bool $favorita = false): Cuenta
    {
        return Cuenta::firstOrCreate(
            ['user_id' => $usuario->id, 'nombre' => $nombre],
            ['tipo' => $tipo, 'saldo_inicial' => $saldo, 'favorita' => $favorita, 'moneda' => 'COP'],
        );
    }

    private function movimiento(Cuenta $cuenta, TipoMovimiento $tipo, CategoriaMovimiento $categoria,
        string $descripcion, float $monto, $fecha): void
    {
        Movimiento::firstOrCreate([
            'user_id' => $cuenta->user_id,
            'cuenta_id' => $cuenta->id,
            'descripcion' => $descripcion,
            'fecha' => $fecha->toDateString(),
        ], [
            'tipo' => $tipo,
            'categoria' => $categoria->value,
            'monto' => $monto,
        ]);
    }
}
