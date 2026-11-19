<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Zona;
use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use App\Repositories\Contracts\ZonaRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de Padrón PadronService (Componente MODELO - Lógica de Negocio).
 *
 * Encapsula la consolidación y consulta del padrón único de damnificados y zonas afectadas
 * mediante abstracciones de repositorios (Principio DIP + Patrón Repository - RF05, HU03).
 */
class PadronService
{
    private DamnificadoRepositoryInterface $damnificadoRepository;
    private ZonaRepositoryInterface $zonaRepository;

    public function __construct(
        DamnificadoRepositoryInterface $damnificadoRepository,
        ZonaRepositoryInterface $zonaRepository
    ) {
        $this->damnificadoRepository = $damnificadoRepository;
        $this->zonaRepository = $zonaRepository;
    }

    /**
     * Obtiene el padrón consolidado de damnificados con su zona asociada,
     * permitiendo filtrado opcional por zona de emergencia.
     */
    public function obtenerPadronConsolidado(?int $zonaId = null, int $porPagina = 25): LengthAwarePaginator
    {
        return $this->damnificadoRepository->obtenerPaginadosPorZona($zonaId, $porPagina);
    }

    /**
     * Lista todas las zonas registradas con el conteo consolidado de damnificados.
     *
     * @return Collection<int, Zona>
     */
    public function listarZonasActivas(): Collection
    {
        return $this->zonaRepository->listarConConteoDamnificados();
    }
}
