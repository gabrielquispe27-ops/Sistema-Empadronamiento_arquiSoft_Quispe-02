<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * Muestra la vista de reportes estadísticos o retorna las métricas JSON por zona.
     *
     * @OA\Get(
     *     path="/api/reportes",
     *     operationId="reportesObtenerMetricasPorZona",
     *     tags={"Reportes Estadísticos"},
     *     summary="Obtiene métricas de empadronamiento y porcentaje de cobertura por zona afectada (Exclusivo Coordinador)",
     *     description="Calcula el total de personas empadronadas y el porcentaje de cobertura respecto a la población estimada de cada zona, almacenando el resultado en caché Redis por 60 segundos (RF06, HU04, ADR-003).",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métricas consolidadas por zona de emergencia",
     *         @OA\JsonContent(ref="#/components/schemas/ReporteMetricasResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autenticado - Token Bearer Sanctum inválido o ausente",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Prohibido - El usuario autenticado no posee el rol 'coordinador'",
     *         @OA\JsonContent(ref="#/components/schemas/ForbiddenErrorResponseSchema")
     *     )
     * )
     */
    public function index(Request $request): View|JsonResponse
    {
        $metricas = $this->reporteService->obtenerMetricasPorZona();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($metricas, 200);
        }

        return view('reportes.index', [
            'metricas' => $metricas,
        ]);
    }
}
