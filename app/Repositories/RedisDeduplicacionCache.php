<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\DeduplicacionCacheInterface;
use Illuminate\Contracts\Redis\Factory as RedisFactory;

/**
 * Implementación Redis (Predis) del contrato de Deduplicación en Memoria.
 *
 * Recibe por inyección de dependencias la factoría de conexiones Redis de Laravel,
 * evitando llamadas estáticas a Redis::setnx() en la capa de lógica de negocio.
 */
class RedisDeduplicacionCache implements DeduplicacionCacheInterface
{
    private const REDIS_DNI_PREFIX = 'dni:';

    private RedisFactory $redis;

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * {@inheritDoc}
     */
    public function reservarDniAtomicamente(string $dni, int $ttlSeconds = 86400): bool
    {
        $claveRedis = self::REDIS_DNI_PREFIX . $dni;
        $connection = $this->redis->connection();

        $reservado = (bool) $connection->command('setnx', [$claveRedis, '1']);

        if (! $reservado) {
            return false;
        }

        $connection->command('expire', [$claveRedis, $ttlSeconds]);

        return true;
    }
}
