<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MovimientoRequest;
use App\Http\Resources\MovimientoResource;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function store(MovimientoRequest $request): JsonResponse
    {
        $movimiento = Movimiento::create($request->validated());

        return response()->json(['data' => new MovimientoResource($movimiento)], 201);
    }

    public function show(Movimiento $movimiento): JsonResponse
    {
        return response()->json(['data' => new MovimientoResource($movimiento)]);
    }

    public function update(MovimientoRequest $request, Movimiento $movimiento): JsonResponse
    {
        $movimiento->update($request->validated());

        return response()->json(['data' => new MovimientoResource($movimiento)]);
    }

    public function destroy(Movimiento $movimiento): JsonResponse
    {
        $movimiento->delete();

        return response()->json(status: 204);
    }
}
