<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Zona;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato del Repositorio de Zonas de Emergencia (Patrón Repository + Principio DIP).
 *
 * Abstrae la consulta de zonas afectadas y el conteo consolidado de damnificados
 * para los servicios de Padrón y Reportes (RF05, RF06).
 */
interface ZonaRepositoryInterface
{
    /**
     * Lista todas las zonas registradas con el conteo consolidado de damnificados.
     *
     * @return Collection<int, Zona>
     */
    public function listarConConteoDamnificados(): Collection;
}
