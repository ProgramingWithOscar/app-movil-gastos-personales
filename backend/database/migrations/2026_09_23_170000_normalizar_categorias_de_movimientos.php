<?php

use App\Enums\CategoriaMovimiento;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deja `categoria` lista para validarse contra App\Enums\CategoriaMovimiento.
 *
 * Hasta ahora era texto libre de 255 caracteres con el catálogo viviendo dentro
 * de la app. Esta migración renombra lo que el documento llama de otra forma,
 * recoge cualquier valor suelto en `otros` y estrecha la columna.
 *
 * Al escribirla, en la base solo había dos movimientos de ingreso y ningún
 * `negocio` ni `inversiones`, así que de las equivalencias solo llegó a usarse
 * `freelance`. Las otras dos se quedan porque la migración también corre en
 * bases donde sí existen.
 */
return new class extends Migration
{
    /**
     * Nombres viejos de la app y su equivalente en el documento (§Módulo 4).
     *
     * `negocio` → `venta` e `inversiones` → `intereses` no son sinónimos
     * exactos, pero son la categoría del documento más cercana; dejarlas fuera
     * del enum anularía el propósito de tener enum.
     */
    private const EQUIVALENCIAS = [
        'freelance' => 'trabajo_independiente',
        'negocio' => 'venta',
        'inversiones' => 'intereses',
    ];

    public function up(): void
    {
        foreach (self::EQUIVALENCIAS as $viejo => $nuevo) {
            DB::table('movimientos')->where('categoria', $viejo)->update(['categoria' => $nuevo]);
        }

        // Cualquier cosa que no esté en el enum —una categoría fantasma de una
        // app vieja, el `general` que era el valor por defecto de la columna—
        // pasa a `otros`, que es la única respuesta honesta: se sabe que el
        // movimiento existe, no en qué se clasificaba.
        DB::table('movimientos')
            ->whereNotIn('categoria', array_column(CategoriaMovimiento::cases(), 'value'))
            ->update(['categoria' => CategoriaMovimiento::Otros->value]);

        Schema::table('movimientos', function (Blueprint $table) {
            // 40 caracteres: el valor más largo del enum es
            // `trabajo_independiente`, de 21. Y el valor por defecto deja de
            // ser `general`, que no existe en el catálogo y habría vuelto a
            // meter categorías inválidas por la puerta de atrás.
            $table->string('categoria', 40)->default(CategoriaMovimiento::Otros->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->string('categoria', 255)->default('general')->change();
        });

        foreach (self::EQUIVALENCIAS as $viejo => $nuevo) {
            DB::table('movimientos')->where('categoria', $nuevo)->update(['categoria' => $viejo]);
        }

        // Lo que se recogió en `otros` no vuelve: no se guardó de dónde venía.
        // Revertir esta migración devuelve la forma de la columna, no los
        // nombres que había antes.
    }
};
