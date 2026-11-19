<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pruebas Funcionales (Feature) de Autenticación con Laravel Sanctum (ADR-004, AC05).
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_api_exitoso_emite_token_bearer_sanctum(): void
    {
        User::factory()->create([
            'name' => 'Brigadista Test',
            'email' => 'brigadista@defensacivil.gob.pe',
            'password' => Hash::make('Brigadista2026!'),
            'role' => User::ROLE_BRIGADISTA,
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'brigadista@defensacivil.gob.pe',
            'password' => 'Brigadista2026!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'token',
                'token_type',
            ])
            ->assertJson([
                'status' => 'ok',
                'token_type' => 'Bearer',
            ]);
    }

    public function test_login_api_rechaza_credenciales_incorrectas_con_422(): void
    {
        User::factory()->create([
            'email' => 'brigadista@defensacivil.gob.pe',
            'password' => Hash::make('Brigadista2026!'),
            'role' => User::ROLE_BRIGADISTA,
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'brigadista@defensacivil.gob.pe',
            'password' => 'ClaveIncorrecta999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_api_valida_formato_de_email_y_longitud_de_password(): void
    {
        $response = $this->postJson('/api/auth/token', [
            'email' => 'correo-invalido',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}
