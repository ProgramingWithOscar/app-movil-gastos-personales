<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Forma de la respuesta del dashboard.
 *
 * Las claves de las fases siguientes (saldo, ingresos, presupuestos, metas) se
 * añadirán a este mismo objeto cuando existan sus módulos. La app no debe
 * asumir que una clave está presente.
 *
 * @property-read array $resource
 */
class DashboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tiene_movimientos' => $this->resource['tiene_movimientos'],
            'saldo' => $this->resource['saldo'],
            'periodo' => $this->resource['periodo'],
            'gastos' => $this->resource['gastos'],
            'ingresos' => $this->resource['ingresos'],
            'balance' => $this->resource['balance'],
            'comparacion' => $this->resource['comparacion'],
            'comparacion_ingresos' => $this->resource['comparacion_ingresos'],
            'presupuestos' => $this->resource['presupuestos'],
            'movimientos_recientes' => MovimientoResource::collection($this->resource['movimientos_recientes']),
        ];
    }
}
