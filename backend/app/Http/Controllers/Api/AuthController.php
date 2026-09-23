<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        event(new Registered($user));

        return $this->tokenResponse($user, $this->deviceName($request), 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        // Un solo mensaje para "no existe" y "contraseña incorrecta": revelar
        // cuál de los dos es filtraría qué correos están registrados.
        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ])->status(401);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta está suspendida.'],
            ])->status(403);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->tokenResponse($user, $this->deviceName($request));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(status: 204);
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => new UserResource($user),
        ], $status);
    }

    /**
     * Nombre con el que se identificará el token en la lista de sesiones.
     */
    private function deviceName(Request $request): string
    {
        $nombre = trim((string) $request->input('device_name'));

        if ($nombre !== '') {
            return mb_substr($nombre, 0, 120);
        }

        return mb_substr($request->userAgent() ?? 'Dispositivo desconocido', 0, 120);
    }
}
