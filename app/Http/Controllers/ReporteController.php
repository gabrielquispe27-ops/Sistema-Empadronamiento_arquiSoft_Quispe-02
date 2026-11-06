<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Contracts\View\View;

/**
 * Controlador de Reportes ReporteController (Componente CONTROLADOR).
 *
 * Atiende las solicitudes de reportes estadísticos por zona afectada para el Coordinador
 * de Defensa Civil, delegando el cálculo y caché en ReporteService (RF06, HU04).
 */
class ReporteController extends Controller
{
    private ReporteService $reporteService;

    public function __construct(ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Muestra la vista de reportes estadísticos y avance de cobertura por zona.
     */
    public function index(): View
    {
        $metricas = $this->reporteService->obtenerMetricasPorZona();

        return view('reportes.index', [
            'metricas' => $metricas,
        ]);
    }
}
