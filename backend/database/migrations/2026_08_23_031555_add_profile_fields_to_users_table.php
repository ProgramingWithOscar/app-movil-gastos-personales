<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default(UserRole::User->value)->after('password');
            $table->string('status', 20)->default(UserStatus::Active->value)->after('role');
            $table->string('avatar_path')->nullable()->after('status');
            $table->char('default_currency', 3)->default('COP')->after('avatar_path');
            $table->char('country', 2)->nullable()->after('default_currency');
            $table->string('timezone', 64)->default('America/Bogota')->after('country');
            $table->string('locale', 5)->default('es')->after('timezone');
            $table->string('theme', 10)->default('system')->after('locale');
            $table->json('notification_preferences')->nullable()->after('theme');
            $table->timestamp('last_login_at')->nullable()->after('notification_preferences');
            $table->softDeletes();

            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'role',
                'status',
                'avatar_path',
                'default_currency',
                'country',
                'timezone',
                'locale',
                'theme',
                'notification_preferences',
                'last_login_at',
                'deleted_at',
            ]);
        });
    }
};
