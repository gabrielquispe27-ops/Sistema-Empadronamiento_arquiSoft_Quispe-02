<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDamnificadoRequest;
use App\Services\Contracts\EmpadronamientoServiceInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controlador de Empadronamiento EmpadronamientoController (Componente CONTROLADOR).
 *
 * Orquesta las peticiones HTTP de captura en campo (RF01, RF04).
 * Depende exclusivamente de la abstracción EmpadronamientoServiceInterface (Principio DIP),
 * manteniendo el controlador libre de lógica de negocio y de acoplamiento a Redis o Eloquent.
 */
class EmpadronamientoController extends Controller
{
    private EmpadronamientoServiceInterface $service;

    public function __construct(EmpadronamientoServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * Muestra el formulario Mobile-First de captura de damnificados (Componente VISTA).
     */
    public function create(): View
    {
        return view('captura.create');
    }

    /**
     * Recibe la petición validada, delega el registro asíncrono al Service y retorna
     * confirmación inmediata (< 2 s) al brigadista en campo (AC01).
     */
    public function store(StoreDamnificadoRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $datos['registrado_por'] = $request->user()?->id;

        $encolado = $this->service->registrar($datos);

        if (! $encolado) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors([
                    'dni' => 'El DNI ingresado ya se encuentra registrado o en proceso de empadronamiento (RF03).',
                ]);
        }

        return redirect()
            ->route('captura.create')
            ->with('status', 'Registro recibido y encolado para procesamiento asíncrono.');
    }
}
