<?php

declare(strict_types=1);

namespace App\Services\Contracts;

/**
 * Contrato del Servicio de Empadronamiento (Componente MODELO - Lógica de Negocio).
 *
 * Aplica el Principio de Inversión de Dependencias (DIP) para desacoplar
 * el Controlador (EmpadronamientoController) de la implementación concreta
 * de validación, deduplicación en Redis y encolamiento asíncrono.
 */
interface EmpadronamientoServiceInterface
{
    /**
     * Valida las reglas de negocio del registro, verifica duplicados en memoria
     * y despacha el DTO a la cola asíncrona de empadronamiento.
     *
     * @param  array<string, mixed>  $datos
     * @return bool
     */
    public function registrar(array $datos): bool;

    /**
     * Comprueba y reserva de forma atómica si el DNI ya fue procesado o encolado.
     *
     * @param  string  $dni
     * @return bool
     */
    public function esDuplicado(string $dni): bool;
}
