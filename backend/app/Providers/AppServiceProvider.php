<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Los endpoints de autenticación se limitan por IP y por correo a la vez:
     * así no basta con rotar IPs para hacer fuerza bruta sobre una cuenta, ni
     * con rotar correos para machacar desde una misma IP.
     */
    private function configureRateLimiting(): void
    {
        // El mensaje del 429 lo genera el framework en inglés: se sustituye
        // por uno propio, que además dice cuánto falta.
        $aviso = fn (Request $request, array $cabeceras) => response()->json([
            'message' => 'Demasiados intentos. Espera '.($cabeceras['Retry-After'] ?? 60).' segundos.',
        ], 429, $cabeceras);

        RateLimiter::for('auth', function (Request $request) use ($aviso) {
            $email = mb_strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('auth-ip:'.$request->ip())->response($aviso),
                Limit::perMinute(5)->by('auth-email:'.$email.'|'.$request->ip())->response($aviso),
            ];
        });

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)
            ->by($request->user()?->id ?: $request->ip()));
    }
}
