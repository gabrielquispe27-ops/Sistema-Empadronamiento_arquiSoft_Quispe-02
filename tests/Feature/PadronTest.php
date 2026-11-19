<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Damnificado;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas Funcionales (Feature) del Endpoint GET /api/padron (RF05, HU03, AC05).
 */
class PadronTest extends TestCase
{
    use RefreshDatabase;

    public function test_rechaza_acceso_a_padron_sin_token_con_401(): void
    {
        $response = $this->getJson('/api/padron');

        $response->assertStatus(401);
    }

    public function test_prohibe_acceso_a_padron_para_rol_brigadista_con_403(): void
    {
        $brigadista = User::factory()->brigadista()->create();
        Sanctum::actingAs($brigadista, ['captura:write']);

        $response = $this->getJson('/api/padron');

        $response->assertStatus(403);
    }

    public function test_coordinador_consulta_padron_consolidado_y_filtra_por_zona(): void
    {
        $coordinador = User::factory()->coordinador()->create();
        $zona1 = Zona::factory()->create(['nombre' => 'Sector Huamanga Centro']);
        $zona2 = Zona::factory()->create(['nombre' => 'Distrito Carmen Alto']);

        Damnificado::factory()->create([
            'dni' => '70000001',
            'zona_id' => $zona1->id,
        ]);
        Damnificado::factory()->create([
            'dni' => '70000002',
            'zona_id' => $zona2->id,
        ]);

        Sanctum::actingAs($coordinador, ['padron:read']);

        $response = $this->getJson('/api/padron?zona_id=' . $zona1->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'zona_seleccionada' => $zona1->id,
            ])
            ->assertJsonCount(1, 'padron.data')
            ->assertJsonPath('padron.data.0.dni', '70000001');
    }
}
