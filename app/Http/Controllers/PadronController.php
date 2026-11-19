<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PadronService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Padrón PadronController (Componente CONTROLADOR).
 *
 * Coordina la visualización del padrón único consolidado para el Coordinador
 * de Defensa Civil, delegando las consultas a PadronService y entregando los datos
 * a la vista Blade del Dashboard o en JSON vía API REST (RF05, HU03).
 */
class PadronController extends Controller
{
    private PadronService $padronService;

    public function __construct(PadronService $padronService)
    {
        $this->padronService = $padronService;
    }

    /**
     * Renderiza el Dashboard o retorna en JSON el padrón consolidado con filtro por zona afectada.
     *
     * @OA\Get(
     *     path="/api/padron",
     *     operationId="padronConsultarConsolidado",
     *     tags={"Padrón Consolidado"},
     *     summary="Consulta el padrón único consolidado de damnificados (Exclusivo Coordinador)",
     *     description="Retorna la lista paginada de personas damnificadas sin duplicados junto con las zonas de emergencia activas. Requiere token Bearer Sanctum y rol 'coordinador' (RF05, HU03, AC05).",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="zona_id",
     *         in="query",
     *         required=false,
     *         description="Filtra los damnificados por ID de zona de emergencia",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Número de página del padrón consolidado (25 registros por página)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Padrón único consolidado obtenido exitosamente",
     *         @OA\JsonContent(ref="#/components/schemas/PadronPaginadoResponseSchema")
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
        $zonaId = $request->filled('zona_id') ? (int) $request->query('zona_id') : null;

        $damnificados = $this->padronService->obtenerPadronConsolidado($zonaId);
        $zonas = $this->padronService->listarZonasActivas();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'ok',
                'zona_seleccionada' => $zonaId,
                'padron' => $damnificados,
                'zonas' => $zonas,
            ], 200);
        }

        return view('dashboard.index', [
            'damnificados' => $damnificados,
            'zonas' => $zonas,
            'zonaSeleccionada' => $zonaId,
        ]);
    }
}
