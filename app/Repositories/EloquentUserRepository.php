<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;

/**
 * Implementación Eloquent/MySQL del Repositorio de Usuarios.
 */
class EloquentUserRepository implements UserRepositoryInterface
{
    private User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function buscarPorEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->model->newQuery()
            ->where('email', $email)
            ->first();

        return $user;
    }
}
