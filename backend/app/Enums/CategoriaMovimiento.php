<?php

namespace App\Enums;

/**
 * Las categorías en las que se clasifica un movimiento.
 *
 * Hasta ahora este catálogo vivía dentro de la app y el servidor aceptaba
 * cualquier cadena. Funcionaba porque solo hay un cliente y lo mandaba bien,
 * pero una versión vieja que escribiera `salarios` en vez de `salario` habría
 * creado una categoría fantasma sin que nada chillara, y los presupuestos por
 * categoría —que comparan cadenas— habrían dejado de contar en silencio.
 *
 * Las de ingreso son las del documento (§Módulo 4); las de gasto, las que ya
 * usaba la app.
 *
 * El nombre visible **no** está aquí sino en los `categorias.php` de `lang`:
 * tiene que cambiar con el idioma del perfil, y un `match` en PHP no se
 * traduce.
 */
enum CategoriaMovimiento: string
{
    // Ingresos
    case Salario = 'salario';
    case TrabajoIndependiente = 'trabajo_independiente';
    case Venta = 'venta';
    case Regalo = 'regalo';
    case Reembolso = 'reembolso';
    case Intereses = 'intereses';

    // Gastos
    case Alimentacion = 'alimentacion';
    case Transporte = 'transporte';
    case Vivienda = 'vivienda';
    case Servicios = 'servicios';
    case Salud = 'salud';
    case Entretenimiento = 'entretenimiento';
    case Compras = 'compras';

    // De los dos
    case Otros = 'otros';

    public function label(): string
    {
        return __('categorias.'.$this->value);
    }

    /**
     * A qué tipos de movimiento aplica.
     *
     * Casi todas pertenecen a uno solo, pero `otros` hace falta en los dos y
     * comparte valor porque es el mismo concepto. Por eso esto devuelve una
     * lista y no un tipo suelto.
     *
     * @return array<int, TipoMovimiento>
     */
    public function tipos(): array
    {
        return match ($this) {
            self::Salario, self::TrabajoIndependiente, self::Venta,
            self::Regalo, self::Reembolso, self::Intereses => [TipoMovimiento::Ingreso],

            self::Alimentacion, self::Transporte, self::Vivienda, self::Servicios,
            self::Salud, self::Entretenimiento, self::Compras => [TipoMovimiento::Gasto],

            self::Otros => [TipoMovimiento::Ingreso, TipoMovimiento::Gasto],
        };
    }

    public function aplicaA(TipoMovimiento $tipo): bool
    {
        return in_array($tipo, $this->tipos(), true);
    }

    public function icono(): string
    {
        return match ($this) {
            self::Salario => 'briefcase-outline',
            self::TrabajoIndependiente => 'laptop-outline',
            self::Venta => 'storefront-outline',
            self::Regalo => 'gift-outline',
            self::Reembolso => 'return-down-back-outline',
            self::Intereses => 'trending-up-outline',

            self::Alimentacion => 'restaurant-outline',
            self::Transporte => 'car-outline',
            self::Vivienda => 'home-outline',
            self::Servicios => 'flash-outline',
            self::Salud => 'medkit-outline',
            self::Entretenimiento => 'game-controller-outline',
            self::Compras => 'bag-handle-outline',

            self::Otros => 'ellipsis-horizontal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Salario, self::Alimentacion => '#059669',
            self::TrabajoIndependiente, self::Transporte => '#0284c7',
            self::Venta, self::Vivienda => '#7c3aed',
            self::Intereses, self::Servicios => '#d97706',
            self::Regalo, self::Entretenimiento => '#db2777',
            self::Reembolso, self::Compras => '#0891b2',
            self::Salud => '#dc2626',
            self::Otros => '#6b7280',
        };
    }

    /**
     * Las categorías de un tipo, o todas si no se pide ninguno.
     *
     * @return array<int, self>
     */
    public static function de(?TipoMovimiento $tipo): array
    {
        if ($tipo === null) {
            return self::cases();
        }

        return array_values(array_filter(
            self::cases(),
            fn (self $categoria) => $categoria->aplicaA($tipo),
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function catalogo(?TipoMovimiento $tipo = null): array
    {
        return array_map(fn (self $categoria) => [
            'id' => $categoria->value,
            'nombre' => $categoria->label(),
            'icono' => $categoria->icono(),
            'color' => $categoria->color(),
            'tipos' => array_map(fn (TipoMovimiento $t) => $t->value, $categoria->tipos()),
        ], self::de($tipo));
    }
}
