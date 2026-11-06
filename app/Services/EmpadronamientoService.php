<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RegistrarDamnificadoJob;
use App\Services\Contracts\EmpadronamientoServiceInterface;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

/**
 * Servicio de Empadronamiento EmpadronamientoService (Componente MODELO - Lógica de Negocio).
 *
 * Implementa las reglas de dominio de captura masiva (ADR-002, ADR-003):
 * 1. Validación estricta de formato de DNI (RF02).
 * 2. Deduplicación atómica en memoria O(1) con Redis SETNX (RF03, AC03).
 * 3. Despacho asíncrono del DTO hacia Laravel Queues (RF04, AC01).
 */
class EmpadronamientoService implements EmpadronamientoServiceInterface
{
    private const REDIS_DNI_PREFIX = 'dni:';
    private const REDIS_DNI_TTL_SECONDS = 86400;

    /**
     * Valida las reglas de negocio del registro, verifica duplicados en Redis
     * y encola el DTO en RegistrarDamnificadoJob sin bloquear MySQL.
     *
     * @param  array<string, mixed>  $datos
     */
    public function registrar(array $datos): bool
    {
        $dni = trim((string) ($datos['dni'] ?? ''));

        if (! $this->validarDNI($dni)) {
            throw new InvalidArgumentException('El DNI debe contener exactamente 8 dígitos numéricos.');
        }

        if ($this->esDuplicado($dni)) {
            return false;
        }

        $dto = [
            'dni' => $dni,
            'nombres' => trim((string) $datos['nombres']),
            'apellidos' => trim((string) $datos['apellidos']),
            'zona_id' => (int) $datos['zona_id'],
            'danos_materiales' => trim((string) ($datos['danos_materiales'] ?? '')),
            'registrado_por' => $datos['registrado_por'] ?? null,
        ];

        RegistrarDamnificadoJob::dispatch($dto);

        return true;
    }

    /**
     * Primera barrera anti-duplicados en memoria: reserva atómicamente el DNI en Redis (SETNX).
     * Devuelve true si el DNI ya estaba reservado/registrado previamente.
     */
    public function esDuplicado(string $dni): bool
    {
        $claveRedis = self::REDIS_DNI_PREFIX . $dni;

        $reservado = (bool) Redis::setnx($claveRedis, '1');

        if (! $reservado) {
            return true;
        }

        Redis::expire($claveRedis, self::REDIS_DNI_TTL_SECONDS);

        return false;
    }

    /**
     * Verifica que el DNI cumpla el formato oficial de 8 dígitos numéricos (RF02).
     */
    private function validarDNI(string $dni): bool
    {
        return preg_match('/^\d{8}$/', $dni) === 1;
    }
}
