<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoMovimiento;
use App\Http\Controllers\Controller;
use App\Http\Resources\MovimientoResource;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MovimientoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $movimientos = Movimiento::query()
            ->with('cuenta')
            ->when($request->integer('cuenta_id'), fn ($q, $id) => $q->where('cuenta_id', $id))
            ->when($request->string('tipo')->isNotEmpty(), fn ($q) => $q->where('tipo', $request->string('tipo')))
            ->when($request->string('categoria')->isNotEmpty(), fn ($q) => $q->where('categoria', $request->string('categoria')))
            ->when($request->date('desde'), fn ($q, $desde) => $q->where('fecha', '>=', $desde))
            ->when($request->date('hasta'), fn ($q, $hasta) => $q->where('fecha', '<=', $hasta))
            ->when($request->integer('limite'), fn ($q, $limite) => $q->limit($limite))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return MovimientoResource::collection($movimientos);
    }

    public function store(Request $request): JsonResponse
    {
        $movimiento = Movimiento::create($this->validated($request));

        return response()->json(['data' => new MovimientoResource($movimiento)], 201);
    }

    public function show(Movimiento $movimiento): JsonResponse
    {
        return response()->json(['data' => new MovimientoResource($movimiento)]);
    }

    public function update(Request $request, Movimiento $movimiento): JsonResponse
    {
        $movimiento->update($this->validated($request));

        return response()->json(['data' => new MovimientoResource($movimiento)]);
    }

    public function destroy(Movimiento $movimiento): JsonResponse
    {
        $movimiento->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'cuenta_id' => [
                'required',
                'integer',
                // La cuenta tiene que ser suya y estar activa: en una archivada
                // ya no se registra nada, aunque conserve su historial.
                Rule::exists('cuentas', 'id')
                    ->where('user_id', $request->user()->id)
                    // 0 y no `false`: PDO enlaza los booleanos de forma
                    // distinta según el driver, y en SQLite la comparación no
                    // casaba. Con el entero funciona igual en todos.
                    ->where('archivada', 0),
            ],
            'tipo' => ['required', Rule::enum(TipoMovimiento::class)],
            'descripcion' => ['required', 'string', 'max:255'],
            // El importe va siempre positivo: el signo lo pone el tipo.
            'monto' => ['required', 'numeric', 'min:0.01'],
            'categoria' => ['nullable', 'string', 'max:60'],
            'fecha' => ['required', 'date'],
        ], [
            'cuenta_id.required' => 'Elige la cuenta de la que sale o entra el dinero.',
            'cuenta_id.exists' => 'Esa cuenta no existe, no es tuya o está archivada.',
        ]);
    }
}
