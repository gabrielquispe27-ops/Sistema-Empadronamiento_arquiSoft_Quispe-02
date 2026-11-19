<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Jobs\RegistrarDamnificadoJob;
use App\Repositories\Contracts\DeduplicacionCacheInterface;
use App\Services\EmpadronamientoService;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

/**
 * Pruebas Unitarias de EmpadronamientoService (Lógica de Negocio y DIP).
 *
 * Verifica en aislamiento:
 * 1. Reserva atómica exitosa con mock de DeduplicacionCacheInterface -> retorna true (202) y despacha RegistrarDamnificadoJob.
 * 2. Rechazo por DNI duplicado con mock de DeduplicacionCacheInterface -> retorna false (409) sin encolar Job.
 * 3. Validación de invariante de dominio del DNI de 8 dígitos numéricos (RF02).
 */
class EmpadronamientoServiceTest extends TestCase
{
    public function test_registra_y_encola_damnificado_cuando_dni_es_reservado_atomicamente(): void
    {
        Queue::fake();

        $cacheMock = Mockery::mock(DeduplicacionCacheInterface::class);
        $cacheMock->shouldReceive('reservarDniAtomicamente')
            ->once()
            ->with('71234567', 86400)
            ->andReturn(true);

        $service = new EmpadronamientoService($cacheMock);

        $resultado = $service->registrar([
            'dni' => ' 71234567 ',
            'nombres' => ' Carlos Alberto ',
            'apellidos' => ' Huamán Quispe ',
            'zona_id' => 1,
            'danos_materiales' => ' Colapso parcial de techo de adobe ',
            'registrado_por' => 10,
        ]);

        $this->assertTrue($resultado);
        Queue::assertPushedOn('empadronamiento', RegistrarDamnificadoJob::class);
    }

    public function test_rechaza_registro_y_no_encola_job_cuando_dni_es_duplicado_en_redis(): void
    {
        Queue::fake();

        $cacheMock = Mockery::mock(DeduplicacionCacheInterface::class);
        $cacheMock->shouldReceive('reservarDniAtomicamente')
            ->once()
            ->with('71234567', 86400)
            ->andReturn(false);

        $service = new EmpadronamientoService($cacheMock);

        $resultado = $service->registrar([
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Huamán Quispe',
            'zona_id' => 1,
            'danos_materiales' => 'Colapso parcial de techo',
            'registrado_por' => 10,
        ]);

        $this->assertFalse($resultado);
        Queue::assertNothingPushed();
    }

    public function test_lanza_excepcion_cuando_formato_de_dni_es_invalido(): void
    {
        Queue::fake();

        $cacheMock = Mockery::mock(DeduplicacionCacheInterface::class);
        $cacheMock->shouldNotReceive('reservarDniAtomicamente');

        $service = new EmpadronamientoService($cacheMock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('El DNI debe contener exactamente 8 dígitos numéricos.');

        $service->registrar([
            'dni' => '12345ABC',
            'nombres' => 'Carlos',
            'apellidos' => 'Huamán',
            'zona_id' => 1,
            'danos_materiales' => 'Daño menor',
        ]);
    }
}
