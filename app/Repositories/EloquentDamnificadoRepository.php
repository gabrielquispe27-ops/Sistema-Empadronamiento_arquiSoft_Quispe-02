<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Damnificado;
use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Implementación Eloquent/MySQL del Repositorio de Damnificados.
 *
 * Encapsula las consultas y el upsert idempotente sobre la instancia inyectada
 * del modelo Damnificado, eliminando llamadas estáticas en Jobs y Services.
 */
class EloquentDamnificadoRepository implements DamnificadoRepositoryInterface
{
    private Damnificado $model;

    public function __construct(Damnificado $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function upsertPadron(array $datos): void
    {
        $this->model->newQuery()->upsert(
            [
                [
                    'dni' => (string) $datos['dni'],
                    'nombres' => (string) $datos['nombres'],
                    'apellidos' => (string) $datos['apellidos'],
                    'zona_id' => (int) $datos['zona_id'],
                    'danos_materiales' => (string) ($datos['danos_materiales'] ?? ''),
                    'registrado_por' => isset($datos['registrado_por']) ? (int) $datos['registrado_por'] : null,
                ],
            ],
            ['dni'],
            ['nombres', 'apellidos', 'zona_id', 'danos_materiales', 'updated_at']
        );
    }

    /**
     * {@inheritDoc}
     */
    public function obtenerPaginadosPorZona(?int $zonaId = null, int $porPagina = 25): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with('zona')
            ->orderByDesc('updated_at');

        if ($zonaId !== null) {
            $query->where('zona_id', $zonaId);
        }

        return $query->paginate($porPagina);
    }
}
