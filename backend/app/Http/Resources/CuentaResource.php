<?php

namespace App\Http\Resources;

use App\Models\Cuenta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cuenta
 */
class CuentaResource extends JsonResource
{
    public function __construct($resource, private readonly ?float $saldo = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo->value,
            'tipo_nombre' => $this->tipo->label(),
            'es_dinero' => $this->tipo->esDinero(),
            'moneda' => $this->moneda,
            'saldo_inicial' => (float) $this->saldo_inicial,
            // Si no llega precalculado, se pide a la cuenta. En listas siempre
            // llega, para no hacer una consulta por fila.
            'saldo_actual' => $this->saldo ?? $this->saldoActual(),
            'color' => $this->color,
            'icono' => $this->icono,
            'favorita' => $this->favorita,
            'archivada' => $this->archivada,
            'orden' => $this->orden,
            'movimientos' => $this->whenCounted('movimientos'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
