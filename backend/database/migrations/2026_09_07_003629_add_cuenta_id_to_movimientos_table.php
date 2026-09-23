<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solo añade la columna, nullable. El relleno de los movimientos que ya existen
 * y volverla obligatoria van en el Bloque 3, que es el paso irreversible.
 *
 * `restrictOnDelete` a propósito: si alguien intenta borrar una cuenta con
 * movimientos, la base de datos lo impide. Preferimos un error explícito a un
 * borrado en cascada que se lleve meses de historial.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable()->after('user_id')->constrained('cuentas')->restrictOnDelete();
            $table->index(['cuenta_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->dropIndex(['cuenta_id', 'fecha']);
            $table->dropForeign(['cuenta_id']);
            $table->dropColumn('cuenta_id');
        });
    }
};
