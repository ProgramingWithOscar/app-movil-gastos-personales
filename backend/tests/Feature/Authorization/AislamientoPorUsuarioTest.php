<?php

namespace Tests\Feature\Authorization;

use App\Models\Cuenta;
use App\Models\Movimiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AislamientoPorUsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_gastos_requieren_autenticacion(): void
    {
        $this->getJson('/api/movimientos')->assertUnauthorized();
        $this->postJson('/api/movimientos', [])->assertUnauthorized();
    }

    public function test_solo_devuelve_los_gastos_propios(): void
    {
        $oscar = User::factory()->create();
        $otro = User::factory()->create();

        Movimiento::factory()->count(2)->create(['user_id' => $oscar->id]);
        Movimiento::factory()->count(3)->create(['user_id' => $otro->id]);

        $respuesta = $this->actingAs($oscar, 'sanctum')->getJson('/api/movimientos')->assertOk();

        $this->assertCount(2, $respuesta->json('data'));
    }

    public function test_asigna_el_dueno_automaticamente_al_crear(): void
    {
        $oscar = User::factory()->create();

        $cuenta = Cuenta::factory()->create(['user_id' => $oscar->id]);

        $this->actingAs($oscar, 'sanctum')->postJson('/api/movimientos', [
            'cuenta_id' => $cuenta->id,
            'tipo' => 'gasto',
            'descripcion' => 'Mercado',
            'monto' => 85000,
            'categoria' => 'alimentacion',
            'fecha' => '2026-08-22',
        ])->assertCreated();

        $this->assertDatabaseHas('movimientos', [
            'tipo' => 'gasto',
            'descripcion' => 'Mercado',
            'user_id' => $oscar->id,
        ]);
    }

    public function test_no_puede_leer_un_gasto_ajeno(): void
    {
        $oscar = User::factory()->create();
        $ajeno = Movimiento::factory()->create();

        $this->actingAs($oscar, 'sanctum')
            ->getJson("/api/movimientos/{$ajeno->id}")
            ->assertNotFound();
    }

    public function test_no_puede_editar_un_gasto_ajeno(): void
    {
        $oscar = User::factory()->create();
        $ajeno = Movimiento::factory()->create(['descripcion' => 'Intacto']);

        $cuenta = Cuenta::factory()->create(['user_id' => $oscar->id]);

        $this->actingAs($oscar, 'sanctum')->putJson("/api/movimientos/{$ajeno->id}", [
            'cuenta_id' => $cuenta->id,
            'tipo' => 'gasto',
            'descripcion' => 'Modificado',
            'monto' => 1,
            'fecha' => '2026-08-22',
        ])->assertNotFound();

        $this->assertDatabaseHas('movimientos', ['id' => $ajeno->id, 'descripcion' => 'Intacto']);
    }

    public function test_no_puede_borrar_un_gasto_ajeno(): void
    {
        $oscar = User::factory()->create();
        $ajeno = Movimiento::factory()->create();

        $this->actingAs($oscar, 'sanctum')
            ->deleteJson("/api/movimientos/{$ajeno->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('movimientos', ['id' => $ajeno->id]);
    }

    public function test_la_lista_no_incluye_movimientos_de_otros(): void
    {
        $oscar = User::factory()->create();
        Movimiento::factory()->create(['user_id' => $oscar->id, 'descripcion' => 'Mio']);
        Movimiento::factory()->create(['descripcion' => 'Ajeno']);

        $respuesta = $this->actingAs($oscar, 'sanctum')->getJson('/api/movimientos')->assertOk();

        $this->assertCount(1, $respuesta->json('data'));
        $this->assertSame('Mio', $respuesta->json('data.0.descripcion'));
    }

    public function test_separa_gastos_de_ingresos(): void
    {
        $oscar = User::factory()->create();
        Movimiento::factory()->count(2)->create(['user_id' => $oscar->id]);
        Movimiento::factory()->ingreso()->create(['user_id' => $oscar->id]);

        $respuesta = $this->actingAs($oscar, 'sanctum')
            ->getJson('/api/movimientos?tipo=ingreso')
            ->assertOk();

        $this->assertCount(1, $respuesta->json('data'));
        $this->assertSame('ingreso', $respuesta->json('data.0.tipo'));
    }
}
