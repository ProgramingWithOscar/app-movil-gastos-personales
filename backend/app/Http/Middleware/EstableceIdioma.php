<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decide en qué idioma responde la API.
 *
 * Manda la preferencia guardada de la persona, que es una decisión explícita
 * suya tomada dentro de la app. Si no hay sesión —registro, login, errores de
 * autenticación— responde en español, que es el idioma por defecto.
 *
 * A propósito **no** se mira `Accept-Language`. La WebView manda la cabecera
 * del sistema, así que un teléfono configurado en inglés hacía que el registro
 * contestara en inglés a alguien que nunca pidió ese idioma. El interruptor del
 * idioma está en el perfil; la cabecera del dispositivo no es ese interruptor.
 */
class EstableceIdioma
{
    private const DISPONIBLES = ['es', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->deLaPersona($request) ?? config('app.locale'));

        return $next($request);
    }

    private function deLaPersona(Request $request): ?string
    {
        $locale = $request->user()?->locale;

        return in_array($locale, self::DISPONIBLES, true) ? $locale : null;
    }
}
