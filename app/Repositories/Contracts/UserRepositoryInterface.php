<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\User;

/**
 * Contrato del Repositorio de Usuarios (Patrón Repository + Principio DIP).
 *
 * Abstrae la búsqueda de actores (Brigadista y Coordinador) para el servicio
 * de autenticación sin acoplar la lógica de negocio a consultas estáticas Eloquent.
 */
interface UserRepositoryInterface
{
    /**
     * Busca un usuario por su dirección de correo electrónico.
     */
    public function buscarPorEmail(string $email): ?User;
}
