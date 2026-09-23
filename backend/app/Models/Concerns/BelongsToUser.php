<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Ata un modelo a su dueño.
 *
 * El scope global evita tener que recordar el `where('user_id', ...)` en cada
 * consulta: olvidarlo una sola vez sería una fuga de datos entre usuarios.
 * Cuando no hay sesión (consola, colas, seeders) el scope no se aplica, así que
 * los comandos siguen viendo todo.
 */
trait BelongsToUser
{
    public static function bootBelongsToUser(): void
    {
        static::addGlobalScope('propietario', function (Builder $query) {
            if (Auth::hasUser()) {
                $query->where($query->getModel()->getTable().'.user_id', Auth::id());
            }
        });

        static::creating(function ($model) {
            if ($model->user_id === null && Auth::hasUser()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
