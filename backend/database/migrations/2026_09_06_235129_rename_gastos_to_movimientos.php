<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La tabla pasa a llamarse `movimientos` y gana la columna `tipo`.
 *
 * El documento funcional modela ingresos, gastos y transferencias como un mismo
 * tipo de registro (§8). Mantener una tabla llamada `gastos` que además guarda
 * ingresos sería mentir sobre su contenido, y el momento más barato para
 * renombrarla es ahora, con unas pocas filas reales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('gastos', 'movimientos');

        Schema::table('movimientos', function (Blueprint $table) {
            $table->string('tipo', 12)->default('gasto')->after('user_id');
            $table->index(['user_id', 'tipo', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'tipo', 'fecha']);
            $table->dropColumn('tipo');
        });

        Schema::rename('movimientos', 'gastos');
    }
};
