<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_cerrar_sesion_revoca_el_token_usado(): void
    {
        $usuario = User::factory()->create();
        $token = $usuario->createToken('Pixel 8')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // El guard cachea el usuario resuelto dentro del mismo test; hay que
        // olvidarlo para que la segunda petición vuelva a validar el token.
        $this->app['auth']->forgetGuards();

        // El mismo token ya no debe servir.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertUnauthorized();
    }

    public function test_no_afecta_a_los_demas_dispositivos(): void
    {
        $usuario = User::factory()->create();
        $movil = $usuario->createToken('Pixel 8')->plainTextToken;
        $usuario->createToken('Tablet');

        $this->withHeader('Authorization', "Bearer {$movil}")
            ->postJson('/api/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Tablet']);
    }

    public function test_requiere_estar_autenticado(): void
    {
        $this->postJson('/api/auth/logout')->assertUnauthorized();
    }
}
