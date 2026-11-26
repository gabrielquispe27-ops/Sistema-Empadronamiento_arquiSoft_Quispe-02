<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de Registro de Damnificado StoreDamnificadoRequest (Componente CONTROLADOR - Validación HTTP).
 *
 * Intercepta y valida el formato de los campos demográficos y daños materiales (RF01, RF02)
 * antes de que la petición alcance EmpadronamientoController::store.
 */
class StoreDamnificadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'digits:8', 'regex:/^\d{8}$/'],
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'min:1', 'zona_valida'],
            'danos_materiales' => ['required', 'string', 'max:1000'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitud'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitud'],
            'precision_gps' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Mensajes personalizados orientados a la usabilidad en campo (AC02).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dni.required' => 'El número de DNI es obligatorio.',
            'dni.digits' => 'El DNI debe contener exactamente 8 dígitos numéricos (RF02).',
            'dni.regex' => 'El DNI solo debe contener caracteres numéricos.',
            'nombres.required' => 'Los nombres del damnificado son obligatorios.',
            'apellidos.required' => 'Los apellidos del damnificado son obligatorios.',
            'zona_id.required' => 'Debe seleccionar la zona de emergencia afectada.',
            'danos_materiales.required' => 'Debe describir los daños materiales reportados.',
            'latitud.between' => 'La latitud GPS debe estar entre -90 y 90.',
            'latitud.numeric' => 'La latitud GPS no es válida.',
            'longitud.between' => 'La longitud GPS debe estar entre -180 y 180.',
            'longitud.numeric' => 'La longitud GPS no es válida.',
            'latitud.required_with' => 'La ubicación GPS requiere latitud y longitud.',
            'longitud.required_with' => 'La ubicación GPS requiere latitud y longitud.',
            'precision_gps.integer' => 'La precisión del GPS debe ser un número entero de metros.',
            'precision_gps.min' => 'La precisión del GPS no puede ser negativa.',
        ];
    }
}
