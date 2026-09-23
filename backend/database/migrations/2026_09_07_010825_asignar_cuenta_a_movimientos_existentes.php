<?php

use App\Enums\TipoCuenta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ata los movimientos que ya existen a una cuenta y vuelve la columna
 * obligatoria.
 *
 * Es el paso irreversible del módulo: después de esto ningún movimiento puede
 * existir sin cuenta. Por eso el orden importa —crear las cuentas antes de
 * exigir la columna— y por eso los totales se comparan antes y después.
 *
 * A cada usuario con movimientos huérfanos se le crea una cuenta "Efectivo" con
 * saldo inicial 0. Es una suposición honesta: no sabemos de dónde salió ese
 * dinero, y efectivo es el caso más común. La persona puede renombrarla.
 */
return new class extends Migration
{
    public function up(): void
    {
        $usuarios = DB::table('movimientos')
            ->whereNull('cuenta_id')
            ->distinct()
            ->pluck('user_id');

        foreach ($usuarios as $userId) {
            $cuentaId = $this->cuentaPorDefecto($userId);

            DB::table('movimientos')
                ->where('user_id', $userId)
                ->whereNull('cuenta_id')
                ->update(['cuenta_id' => $cuentaId]);
        }

        // Si algo quedara suelto, la columna obligatoria fallaría con un error
        // de base de datos difícil de leer. Mejor detenerse aquí y decir por qué.
        $huerfanos = DB::table('movimientos')->whereNull('cuenta_id')->count();

        if ($huerfanos > 0) {
            throw new RuntimeException("Quedan {$huerfanos} movimientos sin cuenta: revisa antes de continuar.");
        }

        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos', function (Blueprint $table) {
            $table->foreignId('cuenta_id')->nullable()->change();
        });
    }

    /** Reutiliza la cuenta "Efectivo" del usuario si ya la tiene. */
    private function cuentaPorDefecto(int $userId): int
    {
        $existente = DB::table('cuentas')
            ->where('user_id', $userId)
            ->where('nombre', 'Efectivo')
            ->value('id');

        if ($existente !== null) {
            return $existente;
        }

        return DB::table('cuentas')->insertGetId([
            'user_id' => $userId,
            'nombre' => 'Efectivo',
            'tipo' => TipoCuenta::Efectivo->value,
            'moneda' => 'COP',
            'saldo_inicial' => 0,
            'color' => TipoCuenta::Efectivo->color(),
            'icono' => TipoCuenta::Efectivo->icono(),
            'favorita' => false,
            'archivada' => false,
            'orden' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
