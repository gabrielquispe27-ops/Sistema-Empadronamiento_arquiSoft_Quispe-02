<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Autorización por Rol EnsureRoleMiddleware (Componente CONTROLADOR - Patrón Chain of Responsibility).
 *
 * Verifica los permisos del actor autenticado vía Laravel Sanctum (ADR-004, AC05)
 * para restringir el acceso al Dashboard y Reportes exclusivamente al Coordinador de Defensa Civil.
 */
class EnsureRoleMiddleware
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'No autenticado. Se requiere un token válido de Laravel Sanctum.');
        }

        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'No tiene permisos suficientes para acceder a este módulo.');
        }

        return $next($request);
    }
}
