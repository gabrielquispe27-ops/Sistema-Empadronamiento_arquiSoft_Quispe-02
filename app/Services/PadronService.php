<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Damnificado;
use App\Models\Zona;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio de Padrón PadronService (Componente MODELO - Lógica de Negocio).
 *
 * Encapsula la consolidación y consulta del padrón único de damnificados y zonas afectadas
 * para alimentar el Dashboard del Coordinador de Defensa Civil (RF05, HU03).
 */
class PadronService
{
    /**
     * Obtiene el padrón consolidado de damnificados con su zona asociada,
     * permitiendo filtrado opcional por zona de emergencia.
     */
    public function obtenerPadronConsolidado(?int $zonaId = null, int $porPagina = 25): LengthAwarePaginator
    {
        $query = Damnificado::query()
            ->with('zona')
            ->orderByDesc('updated_at');

        if ($zonaId !== null) {
            $query->where('zona_id', $zonaId);
        }

        return $query->paginate($porPagina);
    }

    /**
     * Lista todas las zonas registradas con el conteo consolidado de damnificados.
     *
     * @return Collection<int, Zona>
     */
    public function listarZonasActivas(): Collection
    {
        return Zona::query()
            ->withCount('damnificados')
            ->orderBy('nombre')
            ->get();
    }
}
