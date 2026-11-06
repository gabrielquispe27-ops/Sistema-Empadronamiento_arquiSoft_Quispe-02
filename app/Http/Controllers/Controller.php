<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * Controlador Base Abstracto (Componente CONTROLADOR).
 *
 * Punto base para todos los controladores HTTP de la capa de Control.
 * Regla arquitectónica (ADR-002): Los controladores solo coordinan peticiones HTTP,
 * delegan las reglas de negocio a la capa de Services y retornan Vistas Blade o respuestas HTTP.
 */
abstract class Controller
{
}
