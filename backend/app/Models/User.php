<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\UserTheme;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'avatar_path',
    'default_currency',
    'country',
    'timezone',
    'locale',
    'theme',
    'notification_preferences',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Preferencias de notificación por defecto.
     *
     * @var array<string, bool>
     */
    public const DEFAULT_NOTIFICATION_PREFERENCES = [
        'payment_reminder' => true,
        'budget_threshold' => true,
        'budget_exceeded' => true,
        'recurring_upcoming' => true,
        'goal_reached' => true,
        'daily_log_reminder' => false,
    ];

    /**
     * Valores por defecto para que un modelo recién creado ya tenga rol y
     * estado sin depender de los DEFAULT de la base de datos (que no se
     * reflejan en la instancia hasta refrescarla).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'user',
        'status' => 'active',
        'default_currency' => 'COP',
        'timezone' => 'America/Bogota',
        'locale' => 'es',
        'theme' => 'system',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'theme' => UserTheme::class,
            'notification_preferences' => 'array',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Preferencias guardadas, completadas con los valores por defecto.
     * Así una preferencia nueva no queda indefinida para usuarios antiguos.
     *
     * @return array<string, bool>
     */
    public function notificationPreferences(): array
    {
        return array_merge(
            self::DEFAULT_NOTIFICATION_PREFERENCES,
            $this->notification_preferences ?? [],
        );
    }
}
