<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

/**
 * Contrato de Caché de Deduplicación en Memoria (Patrón Repository/Adapter + Principio DIP).
 *
 * Abstrae la reserva atómica de DNI en memoria O(1) para evitar acoplamiento
 * estático con la fachada Redis en la capa de servicios (RF03, AC03, ADR-003).
 */
interface DeduplicacionCacheInterface
{
    /**
     * Intenta reservar atómicamente el DNI en memoria (operación SETNX + TTL).
     * Retorna true si el DNI fue reservado con éxito (nuevo registro),
     * o false si el DNI ya estaba registrado/encolado previamente (duplicado).
     */
    public function reservarDniAtomicamente(string $dni, int $ttlSeconds = 86400): bool;
}
