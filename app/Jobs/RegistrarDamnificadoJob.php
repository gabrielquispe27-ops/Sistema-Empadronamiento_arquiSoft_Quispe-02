<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job Asíncrono RegistrarDamnificadoJob (Componente MODELO - Procesamiento Asíncrono).
 *
 * Actúa como consumidor del patrón Productor-Consumidor (ADR-003, DA01) en los contenedores
 * Worker (queue:work). Recibe un DTO serializable (array) desde Redis y persiste de forma
 * idempotente mediante la abstracción DamnificadoRepositoryInterface inyectada por el
 * contenedor IoC de Laravel (Principio DIP + Patrón Repository).
 */
class RegistrarDamnificadoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de intentos ante fallos transitorios de conexión a MySQL.
     */
    public int $tries = 3;

    /**
     * DTO serializable con los datos validados del damnificado.
     *
     * @var array<string, mixed>
     */
    private array $datosDamnificado;

    /**
     * @param  array<string, mixed>  $datosDamnificado
     */
    public function __construct(array $datosDamnificado)
    {
        $this->datosDamnificado = $datosDamnificado;
        $this->onQueue('empadronamiento');
    }

    /**
     * Ejecuta la persistencia idempotente del registro en el padrón relacional
     * a través del repositorio inyectado por el contenedor de Laravel.
     */
    public function handle(DamnificadoRepositoryInterface $damnificadoRepository): void
    {
        $damnificadoRepository->upsertPadron($this->datosDamnificado);
    }

    /**
     * Gestiona fallos definitivos del trabajo para auditoría y trazabilidad (AC04).
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Fallo al persistir registro de damnificado desde la cola Redis.', [
            'dni' => $this->datosDamnificado['dni'] ?? null,
            'zona_id' => $this->datosDamnificado['zona_id'] ?? null,
            'error' => $exception->getMessage(),
        ]);
    }
}
