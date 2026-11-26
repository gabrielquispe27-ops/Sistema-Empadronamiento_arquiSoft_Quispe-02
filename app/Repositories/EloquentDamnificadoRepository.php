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
        $tieneGps = isset($datos['latitud'], $datos['longitud']);
        $columnasActualizables = ['nombres', 'apellidos', 'zona_id', 'danos_materiales', 'updated_at'];
        if ($tieneGps) {
            // No se pisa una ubicación existente con NULL cuando el reenvío no trae GPS.
            array_push($columnasActualizables, 'latitud', 'longitud', 'precision_gps');
        }

        $this->model->newQuery()->upsert(
            [
                [
                    'dni' => (string) $datos['dni'],
                    'nombres' => (string) $datos['nombres'],
                    'apellidos' => (string) $datos['apellidos'],
                    'zona_id' => (int) $datos['zona_id'],
                    'danos_materiales' => (string) ($datos['danos_materiales'] ?? ''),
                    'registrado_por' => isset($datos['registrado_por']) ? (int) $datos['registrado_por'] : null,
                    'latitud' => $tieneGps ? round((float) $datos['latitud'], 7) : null,
                    'longitud' => $tieneGps ? round((float) $datos['longitud'], 7) : null,
                    'precision_gps' => $tieneGps && isset($datos['precision_gps']) ? (int) $datos['precision_gps'] : null,
                ],
            ],
            ['dni'],
            $columnasActualizables
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
