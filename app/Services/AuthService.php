<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de Autenticación AuthService (Componente MODELO - Lógica de Negocio).
 *
 * Encapsula las reglas de autenticación de actores (Brigadista y Coordinador)
 * y la emisión/revocación de tokens mediante Laravel Sanctum (ADR-004, DA05, AC05).
 */
class AuthService
{
    /**
     * Valida las credenciales del usuario y emite un token de acceso Sanctum.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password): string
    {
        /** @var User|null $user */
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas no son válidas.'],
            ]);
        }

        $abilities = $user->esCoordinador()
            ? ['captura:write', 'padron:read', 'reportes:read']
            : ['captura:write'];

        return $user->createToken('empadronamiento-token', $abilities)->plainTextToken;
    }

    /**
     * Revoca los tokens activos del usuario autenticado al cerrar sesión.
     */
    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }
}
