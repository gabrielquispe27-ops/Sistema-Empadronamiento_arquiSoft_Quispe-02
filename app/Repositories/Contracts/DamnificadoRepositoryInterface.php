<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contrato del Repositorio de Damnificados (Patrón Repository + Principio DIP).
 *
 * Abstrae las operaciones de persistencia idempotente y consulta paginada sobre
 * el padrón consolidado, desacoplando los Services y Jobs del ORM Eloquent.
 */
interface DamnificadoRepositoryInterface
{
    /**
     * Inserta o actualiza de forma idempotente un registro de damnificado
     * basándose en la restricción única física del DNI (RF03, AC03).
     *
     * @param  array<string, mixed>  $datos
     */
    public function upsertPadron(array $datos): void;

    /**
     * Obtiene el padrón consolidado paginado con su zona asociada,
     * permitiendo filtrado opcional por zona de emergencia (RF05).
     */
    public function obtenerPaginadosPorZona(?int $zonaId = null, int $porPagina = 25): LengthAwarePaginator;
}
