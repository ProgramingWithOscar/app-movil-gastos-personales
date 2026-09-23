<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Un límite de gasto para un período y, opcionalmente, una categoría.
 *
 * Sin categoría cubre todo el gasto del período; con categoría, solo esa. Los
 * dos conviven: se puede tener un tope general y otros más finos por dentro.
 */
class Presupuesto extends Model
{
    use BelongsToUser, HasFactory;

    protected $table = 'presupuestos';

    protected $fillable = [
        'categoria',
        'monto',
        'periodo',
        'umbral_alerta',
        'activo',
    ];

    protected $attributes = [
        'periodo' => 'mes',
        'umbral_alerta' => 80,
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'umbral_alerta' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function esGeneral(): bool
    {
        return $this->categoria === null;
    }
}
