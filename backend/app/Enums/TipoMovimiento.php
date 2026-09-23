<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    case Gasto = 'gasto';
    case Ingreso = 'ingreso';

    public function label(): string
    {
        return match ($this) {
            self::Gasto => 'Gasto',
            self::Ingreso => 'Ingreso',
        };
    }

    /** Cómo afecta al saldo: los gastos restan, los ingresos suman. */
    public function signo(): int
    {
        return $this === self::Gasto ? -1 : 1;
    }
}
