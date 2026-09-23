<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        // Los gastos que ya existían son datos de prueba sin dueño: se los
        // asignamos al primer usuario para poder hacer la columna obligatoria.
        $primerUsuario = User::withoutGlobalScopes()->orderBy('id')->first();

        if ($primerUsuario !== null) {
            DB::table('gastos')->whereNull('user_id')->update(['user_id' => $primerUsuario->id]);
        } else {
            DB::table('gastos')->whereNull('user_id')->delete();
        }

        Schema::table('gastos', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->index(['user_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'fecha']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
