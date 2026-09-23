<?php

namespace App\Services;

use App\Enums\TipoMovimiento;
use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Calcula el saldo de todas las cuentas de un usuario en una sola consulta.
 *
 * Pedirle el saldo a cada cuenta por separado sería una consulta por cuenta:
 * con diez cuentas, once viajes a la base de datos para pintar una lista. Aquí
 * se agrupa por cuenta y se resuelve de una vez.
 */
class SaldosCuentas
{
    /**
     * @return array<int, float> saldo por id de cuenta
     */
    public function porUsuario(User $usuario, Collection $cuentas): array
    {
        if ($cuentas->isEmpty()) {
            return [];
        }

        $sumas = Movimiento::withoutGlobalScopes()
            ->selectRaw(
                'cuenta_id,
                 COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) as ingresos,
                 COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) as gastos',
                [TipoMovimiento::Ingreso->value, TipoMovimiento::Gasto->value],
            )
            ->where('user_id', $usuario->id)
            ->whereIn('cuenta_id', $cuentas->pluck('id'))
            ->groupBy('cuenta_id')
            ->get()
            ->keyBy('cuenta_id');

        return $cuentas->mapWithKeys(function (Cuenta $cuenta) use ($sumas) {
            $fila = $sumas->get($cuenta->id);

            $saldo = (float) $cuenta->saldo_inicial
                + (float) ($fila->ingresos ?? 0)
                - (float) ($fila->gastos ?? 0);

            return [$cuenta->id => round($saldo, 2)];
        })->all();
    }

    /**
     * Saldo total, deuda y patrimonio neto.
     *
     * Una tarjeta de crédito en negativo no es "menos dinero": es deuda. Meterla
     * en el mismo saco daría una cifra que no es ni lo que tienes ni lo que
     * debes. Por eso van separadas (spec §4.3).
     *
     * Solo se suman las cuentas en la moneda principal: sumar monedas distintas
     * sin tasa de cambio produce un número sin significado (spec §7).
     *
     * @param  array<int, float>  $saldos
     * @return array<string, mixed>
     */
    public function resumen(User $usuario, Collection $cuentas, array $saldos): array
    {
        $principal = $usuario->default_currency ?: 'COP';
        $total = 0.0;
        $deuda = 0.0;
        $fuera = 0;

        foreach ($cuentas as $cuenta) {
            if ($cuenta->archivada) {
                continue;
            }

            if ($cuenta->moneda !== $principal) {
                $fuera++;
                continue;
            }

            $saldo = $saldos[$cuenta->id] ?? 0.0;

            if ($cuenta->esDinero()) {
                $total += $saldo;
                continue;
            }

            // En una tarjeta, el saldo negativo es lo que se debe.
            $deuda += max(0, -$saldo);
        }

        return [
            'moneda' => $principal,
            'saldo_total' => round($total, 2),
            'deuda' => round($deuda, 2),
            'patrimonio_neto' => round($total - $deuda, 2),
            'cuentas_en_otra_moneda' => $fuera,
        ];
    }
}
