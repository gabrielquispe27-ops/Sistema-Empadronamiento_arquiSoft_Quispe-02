<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Validation\ValidationException;

/**
 * Servicio de Autenticación AuthService (Componente MODELO - Lógica de Negocio).
 *
 * Encapsula las reglas de autenticación de actores (Brigadista y Coordinador)
 * y la emisión/revocación de tokens mediante Laravel Sanctum (ADR-004, DA05, AC05),
 * inyectando UserRepositoryInterface y Hasher (Principio DIP).
 */
class AuthService
{
    private UserRepositoryInterface $userRepository;
    private Hasher $hasher;

    public function __construct(UserRepositoryInterface $userRepository, Hasher $hasher)
    {
        $this->userRepository = $userRepository;
        $this->hasher = $hasher;
    }

    /**
     * Valida las credenciales del usuario y emite un token de acceso Sanctum.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password): string
    {
        $user = $this->userRepository->buscarPorEmail($email);

        if ($user === null || ! $this->hasher->check($password, $user->password)) {
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
