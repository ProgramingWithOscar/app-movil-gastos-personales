<?php

namespace Tests\Feature\Perfil;

use App\Models\User;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PerfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            public function verify($data): bool
            {
                return true;
            }
        });
    }

    public function test_devuelve_el_usuario_autenticado(): void
    {
        $user = User::factory()->create(['email' => 'oscar@ejemplo.test']);

        $this->actingAs($user, 'sanctum')->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.email', 'oscar@ejemplo.test');
    }

    public function test_actualiza_los_datos_del_perfil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user', [
            'name' => 'Oscar Poveda',
            'default_currency' => 'USD',
            'timezone' => 'America/Mexico_City',
            'theme' => 'dark',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Oscar Poveda')
            ->assertJsonPath('data.default_currency', 'USD')
            ->assertJsonPath('data.theme', 'dark');
    }

    public function test_rechaza_una_zona_horaria_inventada(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user', ['timezone' => 'Marte/Olympus'])
            ->assertStatus(422);
    }

    public function test_no_permite_cambiar_el_rol_desde_el_perfil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user', ['role' => 'admin'])->assertOk();

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_cambia_la_contrasena_y_cierra_los_demas_dispositivos(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Antigua123')]);
        $actual = $user->createToken('Pixel 8');
        $user->createToken('Tablet');

        $this->withHeader('Authorization', 'Bearer '.$actual->plainTextToken)
            ->putJson('/api/user/password', [
                'current_password' => 'Antigua123',
                'password' => 'Zafiro7Nubes',
                'password_confirmation' => 'Zafiro7Nubes',
            ])->assertOk();

        $this->assertTrue(Hash::check('Zafiro7Nubes', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'Pixel 8']);
    }

    public function test_exige_la_contrasena_actual_para_cambiarla(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Antigua123')]);

        $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password' => 'Equivocada123',
            'password' => 'Zafiro7Nubes',
            'password_confirmation' => 'Zafiro7Nubes',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');
    }

    public function test_actualiza_las_preferencias_de_notificacion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user/notifications', [
            'preferences' => ['budget_exceeded' => false, 'inventada' => true],
        ])->assertOk()
            ->assertJsonPath('data.notification_preferences.budget_exceeded', false)
            ->assertJsonMissingPath('data.notification_preferences.inventada');
    }

    public function test_sube_un_avatar(): void
    {
        // UploadedFile::fake()->image() necesita GD, que no está en todos los
        // entornos. El endpoint se comprueba igual en el resto de aserciones.
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('La extensión GD no está instalada.');
        }

        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/user/avatar', [
            'avatar' => UploadedFile::fake()->image('foto.jpg'),
        ])->assertOk();

        $this->assertNotNull($user->fresh()->avatar_path);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_rechaza_un_archivo_que_no_es_imagen(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/user/avatar', [
            'avatar' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
        ])->assertStatus(422);
    }

    public function test_elimina_la_cuenta_y_revoca_los_tokens(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Antigua123')]);
        $user->createToken('Pixel 8');

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/user', ['password' => 'Antigua123'])
            ->assertNoContent();

        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_no_elimina_la_cuenta_sin_la_contrasena_correcta(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Antigua123')]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/user', ['password' => 'Equivocada123'])
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);
    }
}
