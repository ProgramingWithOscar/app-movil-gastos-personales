<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // La regla `uncompromised()` consulta la API de HaveIBeenPwned. En los
        // tests la sustituimos para no depender de la red; su comportamiento
        // real se comprueba en test_rechaza_una_contrasena_filtrada().
        $this->fingirVerificadorDeFiltraciones(comprometida: false);
    }

    private function fingirVerificadorDeFiltraciones(bool $comprometida): void
    {
        $this->app->instance(UncompromisedVerifier::class, new class($comprometida) implements UncompromisedVerifier
        {
            public function __construct(private bool $comprometida) {}

            public function verify($data): bool
            {
                return ! $this->comprometida;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'name' => 'Oscar Poveda',
            'email' => 'oscar@ejemplo.test',
            'password' => 'Zafiro7Nubes',
            'password_confirmation' => 'Zafiro7Nubes',
        ], $sobrescribir);
    }

    public function test_registra_un_usuario_y_devuelve_token(): void
    {
        Event::fake([Registered::class]);

        $respuesta = $this->postJson('/api/auth/register', $this->datosValidos());

        $respuesta->assertCreated()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']])
            ->assertJsonPath('user.email', 'oscar@ejemplo.test')
            ->assertJsonPath('user.role', UserRole::User->value)
            ->assertJsonPath('user.status', UserStatus::Active->value);

        $this->assertDatabaseHas('users', ['email' => 'oscar@ejemplo.test']);

        Event::assertDispatched(Registered::class);
    }

    public function test_no_expone_la_contrasena_en_la_respuesta(): void
    {
        $respuesta = $this->postJson('/api/auth/register', $this->datosValidos());

        $this->assertStringNotContainsString('Zafiro7Nubes', $respuesta->getContent());
        $this->assertArrayNotHasKey('password', $respuesta->json('user'));
    }

    public function test_rechaza_un_correo_ya_registrado(): void
    {
        User::factory()->create(['email' => 'oscar@ejemplo.test']);

        $this->postJson('/api/auth/register', $this->datosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_rechaza_una_contrasena_debil(): void
    {
        $this->postJson('/api/auth/register', $this->datosValidos([
            'password' => 'abcdefgh',
            'password_confirmation' => 'abcdefgh',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_rechaza_una_contrasena_filtrada(): void
    {
        $this->fingirVerificadorDeFiltraciones(comprometida: true);

        $this->postJson('/api/auth/register', $this->datosValidos())
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_rechaza_una_contrasena_sin_confirmar(): void
    {
        $this->postJson('/api/auth/register', $this->datosValidos([
            'password_confirmation' => 'OtraCosa123',
        ]))->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_el_token_devuelto_sirve_para_autenticarse(): void
    {
        $token = $this->postJson('/api/auth/register', $this->datosValidos())->json('token');

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertNoContent();
    }
}
