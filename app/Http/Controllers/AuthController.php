<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controlador de Autenticación AuthController (Componente CONTROLADOR).
 *
 * Gestiona el flujo HTTP de inicio y cierre de sesión para Brigadistas y Coordinadores,
 * delegando la validación de credenciales y emisión de tokens Sanctum en AuthService (ADR-004).
 */
class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Renderiza la vista de inicio de sesión (Componente VISTA).
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa la solicitud de autenticación y emite el token de Laravel Sanctum.
     *
     * @OA\Post(
     *     path="/api/auth/token",
     *     operationId="authEmitirTokenSanctum",
     *     tags={"Autenticación"},
     *     summary="Autentica a un Brigadista o Coordinador y emite un token Bearer de Laravel Sanctum",
     *     description="Valida credenciales institucionales y genera un token con habilidades diferenciadas por rol (captura:write para Brigadista; captura:write, padron:read, reportes:read para Coordinador). Trazabilidad: ADR-004, DA05, AC05.",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Credenciales del usuario",
     *         @OA\JsonContent(ref="#/components/schemas/LoginRequestSchema")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autenticación exitosa y emisión de token Bearer Sanctum",
     *         @OA\JsonContent(ref="#/components/schemas/AuthTokenResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Petición malformada o parámetros faltantes",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciales inválidas o usuario no autorizado",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedErrorResponseSchema")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación de formato de correo o credenciales incorrectas",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationErrorResponseSchema")
     *     )
     * )
     */
    public function login(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $datos = $request->validated();

        $token = $this->authService->login(
            (string) $datos['email'],
            (string) $datos['password']
        );

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Autenticación exitosa.',
                'token' => $token,
                'token_type' => 'Bearer',
            ], 200);
        }

        session(['sanctum_token' => $token]);

        return redirect()
            ->route('captura.create')
            ->with('status', 'Sesión iniciada correctamente.');
    }

    /**
     * Revoca los tokens activos del usuario y cierra la sesión.
     */
    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $this->authService->logout($user);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Tokens revocados y sesión finalizada correctamente.',
            ], 200);
        }

        $request->session()->forget('sanctum_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sesión finalizada correctamente.');
    }
}
