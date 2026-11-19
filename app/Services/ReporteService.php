<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\ZonaRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Servicio de Reportes ReporteService (Componente MODELO - Lógica de Negocio).
 *
 * Calcula métricas estadísticas de personas empadronadas y porcentaje de cobertura
 * por zona afectada, inyectando ZonaRepositoryInterface y CacheRepository para cumplir
 * el Principio de Inversión de Dependencias (RF06, HU04).
 */
class ReporteService
{
    private const CACHE_KEY_METRICAS_ZONA = 'reportes:metricas_por_zona';
    private const CACHE_TTL_SECONDS = 60;

    private ZonaRepositoryInterface $zonaRepository;
    private CacheRepository $cache;

    public function __construct(ZonaRepositoryInterface $zonaRepository, CacheRepository $cache)
    {
        $this->zonaRepository = $zonaRepository;
        $this->cache = $cache;
    }

    /**
     * Genera el resumen estadístico de empadronamiento y cobertura por cada zona afectada.
     *
     * @return array<string, mixed>
     */
    public function obtenerMetricasPorZona(): array
    {
        return $this->cache->remember(self::CACHE_KEY_METRICAS_ZONA, self::CACHE_TTL_SECONDS, function (): array {
            $zonas = $this->zonaRepository->listarConConteoDamnificados();

            $totalEmpadronados = 0;
            $detalleZonas = [];

            foreach ($zonas as $zona) {
                $cantidad = (int) $zona->damnificados_count;
                $poblacion = max((int) ($zona->poblacion_estimada ?? 0), 0);
                $cobertura = $poblacion > 0
                    ? round(min(($cantidad / $poblacion) * 100, 100), 2)
                    : 0.0;

                $totalEmpadronados += $cantidad;

                $detalleZonas[] = [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                    'nivel_afectacion' => $zona->nivel_afectacion,
                    'empadronados' => $cantidad,
                    'poblacion_estimada' => $poblacion,
                    'porcentaje_cobertura' => $cobertura,
                ];
            }

            return [
                'total_empadronados' => $totalEmpadronados,
                'total_zonas' => count($detalleZonas),
                'zonas' => $detalleZonas,
            ];
        });
    }
}
