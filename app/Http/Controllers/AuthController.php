<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Contracts\View\View;
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
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        $token = $this->authService->login(
            (string) $datos['email'],
            (string) $datos['password']
        );

        session(['sanctum_token' => $token]);

        return redirect()
            ->route('captura.create')
            ->with('status', 'Sesión iniciada correctamente.');
    }

    /**
     * Revoca los tokens activos del usuario y cierra la sesión.
     */
    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user !== null) {
            $this->authService->logout($user);
        }

        $request->session()->forget('sanctum_token');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Sesión finalizada correctamente.');
    }
}
