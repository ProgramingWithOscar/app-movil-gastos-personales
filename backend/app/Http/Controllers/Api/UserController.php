<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserTheme;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->respuesta($request->user());
    }

    public function update(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:120'],
            'default_currency' => ['sometimes', 'string', 'size:3', Rule::in(['COP', 'USD', 'EUR', 'MXN', 'ARS', 'CLP', 'PEN'])],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'locale' => ['sometimes', 'string', Rule::in(['es', 'en'])],
            'theme' => ['sometimes', Rule::enum(UserTheme::class)],
        ]);

        $user = $request->user();
        $user->update($datos);

        return $this->respuesta($user);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->uncompromised()],
        ]);

        $user = $request->user();

        if (! Hash::check($datos['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta.'],
            ]);
        }

        $user->update(['password' => $datos['password']]);

        // Los demás dispositivos pierden el acceso; el actual sigue dentro.
        $actual = $user->currentAccessToken();
        $user->tokens()->where('id', '!=', $actual?->id)->delete();

        return response()->json(['message' => 'Tu contraseña se actualizó.']);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*' => ['boolean'],
        ]);

        $user = $request->user();

        // Solo aceptamos claves conocidas: así una clave inventada no queda
        // guardada para siempre en el JSON.
        $validas = array_intersect_key(
            $datos['preferences'],
            User::DEFAULT_NOTIFICATION_PREFERENCES,
        );

        $user->update([
            'notification_preferences' => array_merge($user->notificationPreferences(), $validas),
        ]);

        return $this->respuesta($user);
    }

    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $user = $request->user();
        $anterior = $user->avatar_path;

        $ruta = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_path' => $ruta]);

        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return $this->respuesta($user);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['La contraseña no es correcta.'],
            ]);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(status: 204);
    }

    private function respuesta(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user)]);
    }
}
