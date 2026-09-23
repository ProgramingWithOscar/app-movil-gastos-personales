<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actual = $request->user()->currentAccessToken();

        $sesiones = $request->user()->tokens()
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'dispositivo' => $token->name,
                'actual' => $token->id === $actual?->id,
                'ultimo_uso' => $token->last_used_at?->toIso8601String(),
                'creada' => $token->created_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $sesiones]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $token = $request->user()->tokens()->find($id);

        if ($token === null) {
            return response()->json(['message' => 'Esa sesión no existe.'], 404);
        }

        $token->delete();

        return response()->json(status: 204);
    }

    /** Cierra todas menos la del dispositivo desde el que se pide. */
    public function destroyOthers(Request $request): JsonResponse
    {
        $actual = $request->user()->currentAccessToken();

        $request->user()->tokens()->where('id', '!=', $actual?->id)->delete();

        return response()->json(['message' => 'Cerramos la sesión en los demás dispositivos.']);
    }
}
