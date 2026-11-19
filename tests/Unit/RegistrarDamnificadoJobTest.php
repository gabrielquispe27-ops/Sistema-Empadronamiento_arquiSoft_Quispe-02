<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\RegistrarDamnificadoJob;
use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/**
 * Pruebas Unitarias de RegistrarDamnificadoJob (Consumidor Asíncrono y Patrón Repository).
 *
 * Verifica la ejecución idempotente del Job delegando en DamnificadoRepositoryInterface
 * sin invocar métodos estáticos de Eloquent, así como la trazabilidad ante fallos (AC04).
 */
class RegistrarDamnificadoJobTest extends TestCase
{
    public function test_handle_invoca_upsert_idempotente_en_repositorio_inyectado(): void
    {
        $dto = [
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Huamán Quispe',
            'zona_id' => 1,
            'danos_materiales' => 'Inundación de vivienda',
            'registrado_por' => 2,
        ];

        $repositoryMock = Mockery::mock(DamnificadoRepositoryInterface::class);
        $repositoryMock->shouldReceive('upsertPadron')
            ->once()
            ->with($dto);

        $job = new RegistrarDamnificadoJob($dto);
        $job->handle($repositoryMock);

        $this->assertSame('empadronamiento', $job->queue);
        $this->assertSame(3, $job->tries);
    }

    public function test_failed_registra_error_de_auditoria_en_log(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with(
                'Fallo al persistir registro de damnificado desde la cola Redis.',
                Mockery::on(function (array $context): bool {
                    return $context['dni'] === '71234567'
                        && $context['zona_id'] === 1
                        && $context['error'] === 'Timeout de conexión MySQL';
                })
            );

        $job = new RegistrarDamnificadoJob([
            'dni' => '71234567',
            'zona_id' => 1,
        ]);

        $job->failed(new RuntimeException('Timeout de conexión MySQL'));
    }
}
