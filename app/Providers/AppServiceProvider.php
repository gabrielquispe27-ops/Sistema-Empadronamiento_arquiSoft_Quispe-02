<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Contracts\EmpadronamientoServiceInterface;
use App\Services\EmpadronamientoService;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor de Servicios Principal AppServiceProvider (Contenedor de Inyección de Dependencias).
 *
 * Vincula la interfaz EmpadronamientoServiceInterface con su implementación concreta
 * EmpadronamientoService para cumplir el Principio de Inversión de Dependencias (DIP).
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra los contratos y servicios de la capa de negocio en el contenedor IoC.
     */
    public function register(): void
    {
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
