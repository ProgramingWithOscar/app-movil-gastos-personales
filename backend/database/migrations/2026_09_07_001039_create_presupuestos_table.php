<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presupuestos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Sin categoría, el presupuesto es general: cubre todo el gasto del
            // período. Con categoría, solo el de esa categoría.
            $table->string('categoria', 60)->nullable();

            $table->decimal('monto', 12, 2);
            $table->string('periodo', 12)->default('mes');

            // Porcentaje a partir del cual se avisa. El documento pide que sea
            // configurable (§3, Módulo 8), no un 80 % fijo.
            $table->unsignedTinyInteger('umbral_alerta')->default(80);

            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Un solo presupuesto por categoría y período: dos para lo mismo
            // harían imposible decir cuánto queda.
            $table->unique(['user_id', 'categoria', 'periodo']);
            $table->index(['user_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presupuestos');
    }
};
