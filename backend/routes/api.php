<?php

use App\Http\Controllers\Api\Admin\MetricsController;
use App\Http\Controllers\Api\Admin\UserAdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CuentaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MovimientoController;
use App\Http\Controllers\Api\PresupuestoController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['message' => 'pong']);

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('user')->group(function () {
        Route::get('/', [UserController::class, 'show']);
        Route::put('/', [UserController::class, 'update']);
        Route::put('/password', [UserController::class, 'updatePassword']);
        Route::put('/notifications', [UserController::class, 'updateNotifications']);
        Route::post('/avatar', [UserController::class, 'updateAvatar']);
        Route::delete('/', [UserController::class, 'destroy']);

        Route::get('/sessions', [SessionController::class, 'index']);
        Route::delete('/sessions/{id}', [SessionController::class, 'destroy']);
        Route::delete('/sessions', [SessionController::class, 'destroyOthers']);
    });

    Route::get('/categorias', CategoriaController::class);

    Route::get('/dashboard', DashboardController::class);

    Route::put('/cuentas/{cuenta}/favorita', [CuentaController::class, 'favorita']);
    Route::get('/cuentas/{cuenta}/movimientos', [CuentaController::class, 'movimientos']);
    Route::apiResource('cuentas', CuentaController::class);

    Route::apiResource('movimientos', MovimientoController::class);
    Route::apiResource('presupuestos', PresupuestoController::class)->except(['show']);

    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/users', [UserAdminController::class, 'index']);
        Route::put('/users/{user}/status', [UserAdminController::class, 'updateStatus']);
        Route::get('/metrics', MetricsController::class);
    });
});
