<?php

namespace App\Models;

use App\Enums\TipoCuenta;
use App\Enums\TipoMovimiento;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Un lugar donde hay dinero: efectivo, un banco, una billetera, una tarjeta.
 *
 * El saldo **no se guarda**: se calcula desde los movimientos cada vez que se
 * pide. El documento lo exige dos veces (§3 "saldo actual calculado" y §10
 * regla 7). Una columna que se va sumando es más rápida de leer, pero puede
 * dejar de cuadrar con su propio historial, y ese es el peor error posible en
 * una app de dinero: nadie lo nota hasta que ya no se sabe cuál era el número
 * bueno.
 */
class Cuenta extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'cuentas';

    protected $fillable = [
        'nombre',
        'tipo',
        'moneda',
        'saldo_inicial',
        'color',
        'icono',
        'favorita',
        'orden',
    ];

    protected $attributes = [
        'tipo' => 'efectivo',
        'moneda' => 'COP',
        'saldo_inicial' => 0,
        'favorita' => false,
        'archivada' => false,
        'orden' => 0,
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoCuenta::class,
            'saldo_inicial' => 'decimal:2',
            'favorita' => 'boolean',
            'archivada' => 'boolean',
            'orden' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Icono y color por defecto según el tipo: crear una cuenta debe ser
        // nombre y tipo, no una sesión de diseño.
        static::saving(function (self $cuenta) {
            $tipo = $cuenta->tipo instanceof TipoCuenta
                ? $cuenta->tipo
                : TipoCuenta::from((string) $cuenta->tipo);

            $cuenta->icono ??= $tipo->icono();
            $cuenta->color ??= $tipo->color();
        });
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    /**
     * Saldo inicial más lo que entró, menos lo que salió.
     *
     * Una sola consulta agregada en vez de dos: con historiales largos, cada
     * viaje a la base de datos cuenta.
     */
    public function saldoActual(): float
    {
        $sumas = $this->movimientos()
            ->withoutGlobalScopes()
            ->selectRaw(
                'COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) as ingresos,
                 COALESCE(SUM(CASE WHEN tipo = ? THEN monto ELSE 0 END), 0) as gastos',
                [TipoMovimiento::Ingreso->value, TipoMovimiento::Gasto->value],
            )
            ->first();

        return round(
            (float) $this->saldo_inicial + (float) $sumas->ingresos - (float) $sumas->gastos,
            2,
        );
    }

    /** ¿Guarda dinero propio, o deuda? */
    public function esDinero(): bool
    {
        return $this->tipo->esDinero();
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('archivada', false);
    }

    public function scopeArchivadas(Builder $query): Builder
    {
        return $query->where('archivada', true);
    }

    /** La favorita primero; después, el orden que la persona haya dado. */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query->orderByDesc('favorita')->orderBy('orden')->orderBy('nombre');
    }
}
