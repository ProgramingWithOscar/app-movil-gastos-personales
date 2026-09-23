<?php

namespace Tests\Feature\Perfil;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SesionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lista_las_sesiones_y_marca_la_actual(): void
    {
        $user = User::factory()->create();
        $actual = $user->createToken('Pixel 8');
        $user->createToken('Tablet');

        $respuesta = $this->withHeader('Authorization', 'Bearer '.$actual->plainTextToken)
            ->getJson('/api/user/sessions')
            ->assertOk();

        $sesiones = collect($respuesta->json('data'));

        $this->assertCount(2, $sesiones);
        $this->assertTrue($sesiones->firstWhere('dispositivo', 'Pixel 8')['actual']);
        $this->assertFalse($sesiones->firstWhere('dispositivo', 'Tablet')['actual']);
    }

    public function test_revoca_una_sesion_concreta(): void
    {
        $user = User::factory()->create();
        $actual = $user->createToken('Pixel 8');
        $otra = $user->createToken('Tablet');

        $this->withHeader('Authorization', 'Bearer '.$actual->plainTextToken)
            ->deleteJson("/api/user/sessions/{$otra->accessToken->id}")
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseMissing('personal_access_tokens', ['name' => 'Tablet']);
    }

    public function test_no_puede_revocar_la_sesion_de_otra_persona(): void
    {
        $user = User::factory()->create();
        $actual = $user->createToken('Pixel 8');

        $ajeno = User::factory()->create()->createToken('Ajeno');

        $this->withHeader('Authorization', 'Bearer '.$actual->plainTextToken)
            ->deleteJson("/api/user/sessions/{$ajeno->accessToken->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Ajeno']);
    }

    public function test_cierra_las_demas_sesiones(): void
    {
        $user = User::factory()->create();
        $actual = $user->createToken('Pixel 8');
        $user->createToken('Tablet');
        $user->createToken('Portatil');

        $this->withHeader('Authorization', 'Bearer '.$actual->plainTextToken)
            ->deleteJson('/api/user/sessions')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 8']);
    }
}
