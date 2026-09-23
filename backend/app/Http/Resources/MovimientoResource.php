<?php

namespace App\Http\Resources;

use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Movimiento
 */
class MovimientoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo->value,
            'descripcion' => $this->descripcion,
            'monto' => (float) $this->monto,
            'categoria' => $this->categoria,
            'fecha' => $this->fecha?->toDateString(),
            'cuenta' => $this->whenLoaded('cuenta', fn () => [
                'id' => $this->cuenta->id,
                'nombre' => $this->cuenta->nombre,
                'color' => $this->cuenta->color,
                'icono' => $this->cuenta->icono,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
