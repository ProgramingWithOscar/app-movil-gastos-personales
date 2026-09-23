<?php

namespace App\Enums;

/**
 * Los nueve tipos que enumera el documento (§3, Módulo 3).
 *
 * Cada uno trae su icono y su color: así crear una cuenta son dos campos y no
 * seis, y dos cuentas del mismo tipo se ven iguales en toda la app sin que
 * nadie tenga que elegir nada.
 */
enum TipoCuenta: string
{
    case Efectivo = 'efectivo';
    case Bancaria = 'bancaria';
    case Ahorro = 'ahorro';
    case Corriente = 'corriente';
    case Billetera = 'billetera';
    case TarjetaCredito = 'tarjeta_credito';
    case TarjetaDebito = 'tarjeta_debito';
    case Inversion = 'inversion';
    case Otra = 'otra';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Bancaria => 'Cuenta bancaria',
            self::Ahorro => 'Cuenta de ahorro',
            self::Corriente => 'Cuenta corriente',
            self::Billetera => 'Billetera digital',
            self::TarjetaCredito => 'Tarjeta de crédito',
            self::TarjetaDebito => 'Tarjeta débito',
            self::Inversion => 'Cuenta de inversión',
            self::Otra => 'Otra',
        };
    }

    public function icono(): string
    {
        return match ($this) {
            self::Efectivo => 'cash-outline',
            self::Bancaria, self::Corriente => 'business-outline',
            self::Ahorro => 'wallet-outline',
            self::Billetera => 'phone-portrait-outline',
            self::TarjetaCredito => 'card-outline',
            self::TarjetaDebito => 'card-outline',
            self::Inversion => 'trending-up-outline',
            self::Otra => 'ellipsis-horizontal-outline',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Efectivo => '#059669',
            self::Bancaria => '#0284c7',
            self::Ahorro => '#7c3aed',
            self::Corriente => '#0891b2',
            self::Billetera => '#db2777',
            self::TarjetaCredito => '#dc2626',
            self::TarjetaDebito => '#d97706',
            self::Inversion => '#16a34a',
            self::Otra => '#6b7280',
        };
    }

    /**
     * Una tarjeta de crédito no guarda dinero propio: guarda deuda. Sumarla al
     * saldo total daría un número que no es ni lo que tienes ni lo que debes.
     */
    public function esDinero(): bool
    {
        return $this !== self::TarjetaCredito;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function catalogo(): array
    {
        return array_map(fn (self $tipo) => [
            'id' => $tipo->value,
            'nombre' => $tipo->label(),
            'icono' => $tipo->icono(),
            'color' => $tipo->color(),
            'es_dinero' => $tipo->esDinero(),
        ], self::cases());
    }
}
