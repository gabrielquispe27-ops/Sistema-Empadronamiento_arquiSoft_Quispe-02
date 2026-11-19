<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Damnificado;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas Funcionales (Feature) del Endpoint GET /api/reportes (RF06, HU04, AC05).
 */
class ReporteTest extends TestCase
{
    use RefreshDatabase;

    public function test_rechaza_acceso_a_reportes_sin_token_con_401(): void
    {
        $response = $this->getJson('/api/reportes');

        $response->assertStatus(401);
    }

    public function test_prohibe_acceso_a_reportes_para_rol_brigadista_con_403(): void
    {
        $brigadista = User::factory()->brigadista()->create();
        Sanctum::actingAs($brigadista, ['captura:write']);

        $response = $this->getJson('/api/reportes');

        $response->assertStatus(403);
    }

    public function test_coordinador_obtiene_metricas_por_zona_y_almacena_en_cache(): void
    {
        Cache::flush();

        $coordinador = User::factory()->coordinador()->create();
        $zona = Zona::factory()->create([
            'nombre' => 'Sector Huamanga Centro',
            'nivel_afectacion' => 'critico',
            'poblacion_estimada' => 100,
        ]);

        Damnificado::factory()->count(25)->create([
            'zona_id' => $zona->id,
        ]);

        Sanctum::actingAs($coordinador, ['reportes:read']);

        $response = $this->getJson('/api/reportes');

        $response->assertStatus(200)
            ->assertJson([
                'total_empadronados' => 25,
                'total_zonas' => 1,
            ])
            ->assertJsonPath('zonas.0.nombre', 'Sector Huamanga Centro')
            ->assertJsonPath('zonas.0.empadronados', 25)
            ->assertJsonPath('zonas.0.porcentaje_cobertura', 25);

        $this->assertTrue(Cache::has('reportes:metricas_por_zona'));
    }
}
