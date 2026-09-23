<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'role' => $this->role->value,
            'status' => $this->status->value,
            'avatar_url' => $this->avatar_path ? Storage::url($this->avatar_path) : null,
            'default_currency' => $this->default_currency,
            'country' => $this->country,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'theme' => $this->theme->value,
            'notification_preferences' => $this->notificationPreferences(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
