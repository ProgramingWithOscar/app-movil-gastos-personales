<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserStatus;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_usuario_normal_no_entra_al_area_de_administracion(): void
    {
        $oscar = User::factory()->create();

        $this->actingAs($oscar, 'sanctum')->getJson('/api/admin/users')->assertForbidden();
        $this->actingAs($oscar, 'sanctum')->getJson('/api/admin/metrics')->assertForbidden();
    }

    public function test_sin_sesion_el_area_de_administracion_devuelve_401(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }

    public function test_el_administrador_lista_usuarios(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }

    public function test_el_administrador_suspende_a_un_usuario_y_le_revoca_los_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $oscar = User::factory()->create();
        $oscar->createToken('Pixel 8');

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$oscar->id}/status", ['status' => 'suspended'])
            ->assertOk()
            ->assertJsonPath('data.status', UserStatus::Suspended->value);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_el_administrador_no_puede_suspenderse_a_si_mismo(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/admin/users/{$admin->id}/status", ['status' => 'suspended'])
            ->assertStatus(422);
    }

    public function test_las_metricas_son_agregadas_y_no_exponen_movimientos(): void
    {
        $admin = User::factory()->admin()->create();
        $oscar = User::factory()->create();
        Movimiento::factory()->create(['user_id' => $oscar->id, 'descripcion' => 'Psicologo', 'monto' => 250000]);

        $respuesta = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/metrics')->assertOk();

        $respuesta->assertJsonPath('data.actividad.movimientos_registrados', 1);
        $this->assertStringNotContainsString('Psicologo', $respuesta->getContent());
        $this->assertStringNotContainsString('250000', $respuesta->getContent());
    }
}
