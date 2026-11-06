<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PadronService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controlador de Padrón PadronController (Componente CONTROLADOR).
 *
 * Coordina la visualización del padrón único consolidado para el Coordinador
 * de Defensa Civil, delegando las consultas a PadronService y entregando los datos
 * a la vista Blade del Dashboard (RF05, HU03).
 */
class PadronController extends Controller
{
    private PadronService $padronService;

    public function __construct(PadronService $padronService)
    {
        $this->padronService = $padronService;
    }

    /**
     * Renderiza el Dashboard con el padrón consolidado y el filtro por zona afectada.
     */
    public function index(Request $request): View
    {
        $zonaId = $request->filled('zona_id') ? (int) $request->query('zona_id') : null;

        $damnificados = $this->padronService->obtenerPadronConsolidado($zonaId);
        $zonas = $this->padronService->listarZonasActivas();

        return view('dashboard.index', [
            'damnificados' => $damnificados,
            'zonas' => $zonas,
            'zonaSeleccionada' => $zonaId,
        ]);
    }
}
