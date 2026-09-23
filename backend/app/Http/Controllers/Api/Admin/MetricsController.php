<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    /**
     * Métricas agregadas y anonimizadas.
     *
     * Deliberadamente no devuelve ningún movimiento ni importe atribuible a una
     * persona: un administrador gestiona la plataforma, no ve las finanzas
     * ajenas.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'usuarios' => [
                    'total' => User::count(),
                    'activos' => User::where('status', UserStatus::Active)->count(),
                    'suspendidos' => User::where('status', UserStatus::Suspended)->count(),
                    'verificados' => User::whereNotNull('email_verified_at')->count(),
                    'nuevos_ultimos_30_dias' => User::where('created_at', '>=', now()->subDays(30))->count(),
                ],
                'actividad' => [
                    'movimientos_registrados' => Movimiento::withoutGlobalScopes()->count(),
                    'usuarios_con_movimientos' => Movimiento::withoutGlobalScopes()->distinct('user_id')->count('user_id'),
                ],
            ],
        ]);
    }
}
