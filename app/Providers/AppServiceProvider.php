<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Contracts\DamnificadoRepositoryInterface;
use App\Repositories\Contracts\DeduplicacionCacheInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ZonaRepositoryInterface;
use App\Repositories\EloquentDamnificadoRepository;
use App\Repositories\EloquentUserRepository;
use App\Repositories\EloquentZonaRepository;
use App\Repositories\RedisDeduplicacionCache;
use App\Services\Contracts\EmpadronamientoServiceInterface;
use App\Services\EmpadronamientoService;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de Servicios Principal AppServiceProvider (Contenedor de Inyección de Dependencias).
 *
 * Vincula las interfaces de Servicios y Repositorios con sus implementaciones concretas
 * para cumplir el Principio de Inversión de Dependencias (DIP - SOLID) y el patrón Repository.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los contratos de repositorios y servicios de la capa de negocio en el contenedor IoC.
     */
    public function register(): void
    {
        $this->app->bind(
            DamnificadoRepositoryInterface::class,
            EloquentDamnificadoRepository::class
        );

        $this->app->bind(
            DeduplicacionCacheInterface::class,
            RedisDeduplicacionCache::class
        );

        $this->app->bind(
            ZonaRepositoryInterface::class,
            EloquentZonaRepository::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            EloquentUserRepository::class
        );

        $this->app->bind(
            EmpadronamientoServiceInterface::class,
            EmpadronamientoService::class
        );
    }

    /**
     * Inicializa los servicios de la aplicación.
     */
    public function boot(): void
    {
    }
}
