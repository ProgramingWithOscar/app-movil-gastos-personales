<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('nombre', 80);
            $table->string('tipo', 24);
            $table->char('moneda', 3)->default('COP');

            // Negativo permitido: una tarjeta de crédito arranca en deuda.
            $table->decimal('saldo_inicial', 14, 2)->default(0);

            $table->string('color', 9)->nullable();
            $table->string('icono', 40)->nullable();

            $table->boolean('favorita')->default(false);
            $table->boolean('archivada')->default(false);
            $table->unsignedSmallInteger('orden')->default(0);

            $table->timestamps();

            // Dos cuentas con el mismo nombre serían indistinguibles al elegir
            // dónde registrar un movimiento.
            $table->unique(['user_id', 'nombre']);
            $table->index(['user_id', 'archivada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};
