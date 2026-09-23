<?php

namespace App\Http\Controllers\Api;

use App\Enums\TipoCuenta;
use App\Http\Controllers\Controller;
use App\Http\Requests\CuentaRequest;
use App\Http\Resources\CuentaResource;
use App\Http\Resources\MovimientoResource;
use App\Models\Cuenta;
use App\Services\SaldosCuentas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CuentaController extends Controller
{
    public function __construct(private readonly SaldosCuentas $saldos) {}

    public function index(Request $request): JsonResponse
    {
        $cuentas = Cuenta::query()
            ->when(
                ! $request->boolean('incluir_archivadas'),
                fn ($query) => $query->activas(),
            )
            ->withCount('movimientos')
            ->ordenadas()
            ->get();

        $saldos = $this->saldos->porUsuario($request->user(), $cuentas);

        return response()->json([
            'data' => $cuentas->map(fn (Cuenta $cuenta) => new CuentaResource($cuenta, $saldos[$cuenta->id] ?? null)),
            'resumen' => $this->saldos->resumen($request->user(), $cuentas, $saldos),
            'tipos' => TipoCuenta::catalogo(),
        ]);
    }

    public function store(CuentaRequest $request): JsonResponse
    {
        $cuenta = Cuenta::create($request->validated());

        if ($request->boolean('favorita')) {
            $this->marcarFavorita($cuenta);
        }

        return response()->json(['data' => new CuentaResource($cuenta)], 201);
    }

    public function show(Cuenta $cuenta): JsonResponse
    {
        $cuenta->loadCount('movimientos');

        return response()->json(['data' => new CuentaResource($cuenta)]);
    }

    public function update(CuentaRequest $request, Cuenta $cuenta): JsonResponse
    {
        $cuenta->update($request->validated());

        if ($request->boolean('favorita')) {
            $this->marcarFavorita($cuenta);
        }

        return response()->json(['data' => new CuentaResource($cuenta->fresh())]);
    }

    /**
     * Archiva la cuenta en vez de borrarla.
     *
     * Borrarla dejaría huérfanos sus movimientos y con ellos el historial que da
     * sentido a los totales de meses pasados. Solo se borra de verdad si nunca
     * tuvo movimientos: ahí no hay historial que perder.
     */
    public function destroy(Cuenta $cuenta): JsonResponse
    {
        if ($cuenta->movimientos()->withoutGlobalScopes()->doesntExist()) {
            $cuenta->delete();

            return response()->json(['message' => 'Cuenta eliminada.']);
        }

        $cuenta->archivada = true;
        $cuenta->favorita = false;
        $cuenta->save();

        return response()->json([
            'message' => 'Cuenta archivada. Conserva su historial pero deja de sumar.',
            'data' => new CuentaResource($cuenta),
        ]);
    }

    public function favorita(Cuenta $cuenta): JsonResponse
    {
        if ($cuenta->archivada) {
            abort(422, 'Una cuenta archivada no puede ser la favorita.');
        }

        $this->marcarFavorita($cuenta);

        return response()->json(['data' => new CuentaResource($cuenta->fresh())]);
    }

    public function movimientos(Request $request, Cuenta $cuenta): AnonymousResourceCollection
    {
        $movimientos = $cuenta->movimientos()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($request->integer('por_pagina') ?: 25);

        return MovimientoResource::collection($movimientos);
    }

    /** Solo puede haber una favorita: si no, no serviría para preseleccionar. */
    private function marcarFavorita(Cuenta $cuenta): void
    {
        Cuenta::where('id', '!=', $cuenta->id)->update(['favorita' => false]);

        $cuenta->favorita = true;
        $cuenta->save();
    }
}
