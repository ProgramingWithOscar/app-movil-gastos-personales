<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presupuesto;
use App\Services\EvaluadorPresupuestos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PresupuestoController extends Controller
{
    public function __construct(private readonly EvaluadorPresupuestos $evaluador) {}

    /** Devuelve los presupuestos ya cruzados con lo gastado. */
    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->evaluador->evaluar($request->user())]);
    }

    public function store(Request $request): JsonResponse
    {
        $presupuesto = Presupuesto::create($this->validated($request));

        return response()->json(['data' => $presupuesto], 201);
    }

    public function update(Request $request, Presupuesto $presupuesto): JsonResponse
    {
        $presupuesto->update($this->validated($request, $presupuesto));

        return response()->json(['data' => $presupuesto]);
    }

    public function destroy(Presupuesto $presupuesto): JsonResponse
    {
        $presupuesto->delete();

        return response()->json(status: 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Presupuesto $actual = null): array
    {
        return $request->validate([
            'categoria' => [
                'nullable',
                'string',
                'max:60',
                // Dos presupuestos para la misma categoría y período harían
                // imposible decir cuánto queda.
                Rule::unique('presupuestos')
                    ->where('user_id', $request->user()->id)
                    ->where('periodo', $request->input('periodo', 'mes'))
                    ->ignore($actual?->id),
            ],
            'monto' => ['required', 'numeric', 'min:1'],
            'periodo' => ['required', Rule::in(['semana', 'mes'])],
            'umbral_alerta' => ['nullable', 'integer', 'min:1', 'max:100'],
            'activo' => ['nullable', 'boolean'],
        ]);
    }
}
