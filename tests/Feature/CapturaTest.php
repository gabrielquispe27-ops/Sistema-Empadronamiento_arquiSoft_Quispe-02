<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RegistrarDamnificadoJob;
use App\Models\User;
use App\Models\Zona;
use App\Repositories\Contracts\DeduplicacionCacheInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

/**
 * Pruebas Funcionales (Feature) del Endpoint POST /api/captura (RF01-RF04, AC01, AC03, AC05).
 */
class CapturaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rechaza_captura_sin_autenticacion_sanctum_con_401(): void
    {
        $response = $this->postJson('/api/captura', [
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Huamán Quispe',
            'zona_id' => 1,
            'danos_materiales' => 'Vivienda inhabitable',
        ]);

        $response->assertStatus(401);
    }

    public function test_acepta_y_encola_captura_valida_con_202_accepted(): void
    {
        Queue::fake();

        $brigadista = User::factory()->brigadista()->create();
        $zona = Zona::factory()->create();

        $cacheMock = Mockery::mock(DeduplicacionCacheInterface::class);
        $cacheMock->shouldReceive('reservarDniAtomicamente')
            ->once()
            ->with('71234567', 86400)
            ->andReturn(true);

        $this->app->instance(DeduplicacionCacheInterface::class, $cacheMock);

        Sanctum::actingAs($brigadista, ['captura:write']);

        $response = $this->postJson('/api/captura', [
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Huamán Quispe',
            'zona_id' => $zona->id,
            'danos_materiales' => 'Colapso parcial de techo de adobe.',
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'status' => 'queued',
                'dni' => '71234567',
                'queue' => 'empadronamiento',
            ]);

        Queue::assertPushedOn('empadronamiento', RegistrarDamnificadoJob::class);
    }

    public function test_retorna_409_conflict_cuando_dni_es_duplicado_en_redis(): void
    {
        Queue::fake();

        $brigadista = User::factory()->brigadista()->create();
        $zona = Zona::factory()->create();

        $cacheMock = Mockery::mock(DeduplicacionCacheInterface::class);
        $cacheMock->shouldReceive('reservarDniAtomicamente')
            ->once()
            ->with('71234567', 86400)
            ->andReturn(false);

        $this->app->instance(DeduplicacionCacheInterface::class, $cacheMock);

        Sanctum::actingAs($brigadista, ['captura:write']);

        $response = $this->postJson('/api/captura', [
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Huamán Quispe',
            'zona_id' => $zona->id,
            'danos_materiales' => 'Intento duplicado concurrente.',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status' => 'conflict',
                'error_code' => 'DUPLICATE_DNI',
            ]);

        Queue::assertNothingPushed();
    }

    public function test_retorna_422_cuando_dni_no_tiene_8_digitos_o_zona_no_existe(): void
    {
        $brigadista = User::factory()->brigadista()->create();
        Sanctum::actingAs($brigadista, ['captura:write']);

        $response = $this->postJson('/api/captura', [
            'dni' => '1234',
            'nombres' => 'Carlos',
            'apellidos' => 'Huamán',
            'zona_id' => 9999,
            'danos_materiales' => 'Daño estructural',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dni', 'zona_id']);
    }
}
