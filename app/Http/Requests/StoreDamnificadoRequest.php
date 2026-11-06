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
            'zona_id' => ['required', 'integer', 'min:1'],
            'danos_materiales' => ['required', 'string', 'max:1000'],
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
        ];
    }
}
