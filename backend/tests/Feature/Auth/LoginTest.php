<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(array $sobrescribir = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'oscar@ejemplo.test',
            'password' => Hash::make('Contrasena123'),
        ], $sobrescribir));
    }

    public function test_inicia_sesion_con_credenciales_correctas(): void
    {
        $this->usuario();

        $this->postJson('/api/auth/login', [
            'email' => 'oscar@ejemplo.test',
            'password' => 'Contrasena123',
            'device_name' => 'Pixel 8',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email']])
            ->assertJsonPath('user.email', 'oscar@ejemplo.test');

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 8']);
    }

    public function test_registra_la_fecha_del_ultimo_acceso(): void
    {
        $usuario = $this->usuario(['last_login_at' => null]);

        $this->postJson('/api/auth/login', [
            'email' => 'oscar@ejemplo.test',
            'password' => 'Contrasena123',
        ])->assertOk();

        $this->assertNotNull($usuario->fresh()->last_login_at);
    }

    public function test_rechaza_una_contrasena_incorrecta(): void
    {
        $this->usuario();

        $this->postJson('/api/auth/login', [
            'email' => 'oscar@ejemplo.test',
            'password' => 'IncorrectaXyz1',
        ])->assertStatus(401);
    }

    public function test_no_revela_si_el_correo_existe(): void
    {
        $this->usuario();

        $conCorreoReal = $this->postJson('/api/auth/login', [
            'email' => 'oscar@ejemplo.test',
            'password' => 'IncorrectaXyz1',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'noexiste@ejemplo.test',
            'password' => 'IncorrectaXyz1',
        ])->assertStatus($conCorreoReal->status())
            ->assertExactJson($conCorreoReal->json());
    }

    public function test_rechaza_a_un_usuario_suspendido(): void
    {
        User::factory()->suspended()->create([
            'email' => 'suspendido@ejemplo.test',
            'password' => Hash::make('Contrasena123'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'suspendido@ejemplo.test',
            'password' => 'Contrasena123',
        ])->assertStatus(403);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_limita_los_intentos_fallidos(): void
    {
        $this->usuario();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'oscar@ejemplo.test',
                'password' => 'IncorrectaXyz1',
            ])->assertStatus(401);
        }

        $this->postJson('/api/auth/login', [
            'email' => 'oscar@ejemplo.test',
            'password' => 'Contrasena123',
        ])->assertStatus(429);
    }
}
