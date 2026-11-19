<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDamnificadoRequest;
use App\Services\Contracts\EmpadronamientoServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de Empadronamiento EmpadronamientoController (Componente CONTROLADOR).
 *
 * Orquesta las peticiones HTTP de captura en campo (RF01, RF04).
 * Depende exclusivamente de la abstracción EmpadronamientoServiceInterface (Principio DIP),
 * manteniendo el controlador libre de lógica de negocio y de acoplamiento a Redis o Eloquent.
 */
class EmpadronamientoController extends Controller
{
    private EmpadronamientoServiceInterface $service;

    public function __construct(EmpadronamientoServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Muestra el formulario Mobile-First de captura de damnificados (Componente VISTA).
     */
    public function create(): View
    {
        return view('captura.create');
    }

    /**
     * Recibe la petición validada, delega el registro asíncrono al Service y retorna
     * confirmación inmediata (< 2 s) al brigadista en campo (AC01).
     *
     * @OA\Post(
     *     path="/api/captura",
     *     operationId="capturaRegistrarDamnificado",
     *     tags={"Captura / Empadronamiento"},
     *     summary="Registra y encola asíncronamente a un damnificado con deduplicación O(1) en Redis SETNX",
     *     description="Valida el DNI de 8 dígitos (RF02), verifica duplicidad en memoria mediante Redis SETNX (RF03, AC03) y despacha RegistrarDamnificadoJob a la cola 'empadronamiento' respondiendo en < 2 s (RF04, AC01, ADR-003).",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Datos demográficos y daños materiales capturados en campo por el Brigadista",
     *         @OA\JsonContent(ref="#/components/schemas/StoreDamnificadoRequestSchema")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Accepted - Registro validado, reservado en Redis SETNX y encolado en Laravel Queues",
     *         @OA\JsonContent(ref="#/components/schemas/CapturaAcceptedResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request - Formato de DNI inválido o payload malformado",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Token Bearer de Laravel Sanctum ausente o inválido (AC05)",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Conflict - El DNI ingresado ya fue registrado o reservado en Redis SETNX (RF03, AC03)",
     *         @OA\JsonContent(ref="#/components/schemas/ConflictDuplicateDniResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity - Error de validación en StoreDamnificadoRequest",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponseSchema")
     *     )
     * )
     */
    public function store(StoreDamnificadoRequest $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validated();
        $datos['registrado_por'] = $request->user()?->id;

        $encolado = $this->service->registrar($datos);

        if (! $encolado) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'conflict',
                    'error_code' => 'DUPLICATE_DNI',
                    'message' => 'El DNI ingresado ya se encuentra registrado o en proceso de empadronamiento (RF03).',
                ], 409);
            }

            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'dni' => 'El DNI ingresado ya se encuentra registrado o en proceso de empadronamiento (RF03).',
                ]);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'queued',
                'message' => 'Registro recibido y encolado para procesamiento asíncrono.',
                'dni' => (string) $datos['dni'],
                'queue' => 'empadronamiento',
            ], 202);
        }

        return redirect()
            ->route('captura.create')
            ->with('status', 'Registro recibido y encolado para procesamiento asíncrono.');
    }
}
