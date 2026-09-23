<?php

namespace App\Http\Controllers\Api;

use App\Enums\CategoriaMovimiento;
use App\Enums\TipoMovimiento;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    /**
     * El catálogo de categorías, ya traducido al idioma de la persona.
     *
     * Lo sirve el servidor en vez de llevarlo dentro la app porque es el único
     * que puede traducirlo, y porque así una app vieja no puede ofrecer una
     * categoría que ya no exista.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'tipo' => ['nullable', Rule::enum(TipoMovimiento::class)],
        ]);

        $tipo = isset($datos['tipo']) ? TipoMovimiento::from($datos['tipo']) : null;

        return response()->json(['data' => CategoriaMovimiento::catalogo($tipo)]);
    }
}
