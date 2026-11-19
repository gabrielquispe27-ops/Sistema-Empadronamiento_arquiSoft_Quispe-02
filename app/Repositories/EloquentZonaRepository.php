<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Zona;
use App\Repositories\Contracts\ZonaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Implementación Eloquent/MySQL del Repositorio de Zonas de Emergencia.
 */
class EloquentZonaRepository implements ZonaRepositoryInterface
{
    private Zona $model;

    public function __construct(Zona $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function listarConConteoDamnificados(): Collection
    {
        return $this->model->newQuery()
            ->withCount('damnificados')
            ->orderBy('nombre')
            ->get();
    }
}
