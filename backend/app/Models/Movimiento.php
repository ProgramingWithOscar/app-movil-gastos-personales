<?php

namespace App\Models;

use App\Enums\TipoMovimiento;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un movimiento de dinero: por ahora un gasto o un ingreso.
 *
 * El importe se guarda siempre positivo; el signo lo aporta el `tipo`. Guardar
 * negativos obligaría a recordar el signo en cada suma y a que un error de
 * captura pudiera invertir el sentido de un movimiento sin que nada chille.
 */
class Movimiento extends Model
{
    use BelongsToUser, HasFactory;

    protected $fillable = [
        'cuenta_id',
        'tipo',
        'descripcion',
        'monto',
        'categoria',
        'fecha',
    ];

    protected $attributes = [
        'tipo' => 'gasto',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimiento::class,
            'monto' => 'decimal:2',
            'fecha' => 'date:Y-m-d',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function scopeGastos(Builder $query): Builder
    {
        return $query->where('tipo', TipoMovimiento::Gasto);
    }

    public function scopeIngresos(Builder $query): Builder
    {
        return $query->where('tipo', TipoMovimiento::Ingreso);
    }
}
