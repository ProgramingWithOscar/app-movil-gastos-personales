<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserAdminController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $usuarios = User::query()
            ->when($request->string('buscar')->isNotEmpty(), function ($query) use ($request) {
                $termino = '%'.$request->string('buscar').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $termino)->orWhere('email', 'like', $termino));
            })
            ->when($request->string('estado')->isNotEmpty(), fn ($q) => $q->where('status', $request->string('estado')))
            ->orderByDesc('id')
            ->paginate(25);

        return UserResource::collection($usuarios);
    }

    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $datos = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
        ]);

        if ($user->is($request->user())) {
            abort(422, 'No puedes cambiar el estado de tu propia cuenta.');
        }

        // `status` no es asignable en masa a propósito: se cambia explícitamente
        // y solo desde aquí.
        $user->status = UserStatus::from($datos['status']);
        $user->save();

        // Un usuario suspendido no debe seguir operando con sus tokens vivos.
        if (! $user->isActive()) {
            $user->tokens()->delete();
        }

        return response()->json(['data' => new UserResource($user)]);
    }
}
