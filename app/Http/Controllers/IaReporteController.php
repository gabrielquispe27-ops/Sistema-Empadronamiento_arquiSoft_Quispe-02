<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Controlador IaReporteController (Componente CONTROLADOR).
 *
 * Mejora la redacción del reporte de daños materiales usando la API REST de Google Gemini.
 * Es una ayuda opcional: ante falta de API key o fallos de red responde con un mensaje
 * amigable y el brigadista conserva su texto original.
 */
class IaReporteController extends Controller
{
    private const TIMEOUT_SEGUNDOS = 15;

    private const PROMPT_SISTEMA = <<<'PROMPT'
Eres un redactor técnico de Defensa Civil del Perú (estilo INDECI). Recibirás el texto de un brigadista que reporta daños materiales tras una emergencia.

Tu tarea:
- Redactar el reporte en español formal, claro y conciso, con el estilo de los informes de Defensa Civil (INDECI Perú).
- Corregir ortografía, puntuación y gramática.
- Estructurar los daños reportados en un solo párrafo o en viñetas breves (usa "- " al inicio de cada viñeta).
- NO inventar datos: no agregues cifras, ubicaciones, nombres, causas ni daños que no aparezcan en el texto original.
- Mantener todos los datos que sí aparezcan (cantidades, lugares, servicios afectados).

Responde ÚNICAMENTE con el texto corregido: sin saludos, sin explicaciones, sin comillas, sin encabezados y sin formato markdown (nada de **negritas**, #, ni bloques de código). Máximo 900 caracteres.
PROMPT;

    public function mejorar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'texto' => ['required', 'string', 'min:5', 'max:1500'],
        ], [
            'texto.required' => 'Escriba primero el reporte de daños para poder mejorarlo.',
            'texto.min' => 'El texto es muy corto para mejorarlo (mínimo 5 caracteres).',
            'texto.max' => 'El texto es demasiado largo (máximo 1500 caracteres).',
        ]);

        $apiKey = (string) config('services.gemini.key', '');
        $modelo = (string) config('services.gemini.model', 'gemini-2.5-flash');

        if ($apiKey === '' || $modelo === '') {
            return $this->error('La mejora con IA no está configurada. Contacte al administrador.', 503);
        }

        $generationConfig = ['temperature' => 0.2, 'maxOutputTokens' => 2048];
        if (str_contains($modelo, 'flash')) {
            // Evita que el "razonamiento" consuma los tokens de salida en una tarea de redacción simple.
            $generationConfig['thinkingConfig'] = ['thinkingBudget' => 0];
        }

        try {
            // La clave viaja en cabecera (equivalente a ?key=) para no exponerla en URLs ni logs.
            $respuesta = Http::acceptJson()
                ->asJson()
                ->withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout(self::TIMEOUT_SEGUNDOS)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent", [
                    'systemInstruction' => ['parts' => [['text' => self::PROMPT_SISTEMA]]],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [['text' => "Texto del brigadista:\n".trim($datos['texto'])]],
                    ]],
                    'generationConfig' => $generationConfig,
                ]);
        } catch (Throwable) {
            return $this->error('No se pudo conectar con el servicio de IA. Verifique su conexión e intente nuevamente.', 503);
        }

        if (! $respuesta->successful()) {
            return $this->error('El servicio de IA no está disponible en este momento. Intente nuevamente en unos minutos.', 503);
        }

        $partes = $respuesta->json('candidates.0.content.parts');
        $texto = '';
        if (is_array($partes)) {
            foreach ($partes as $parte) {
                $texto .= (string) ($parte['text'] ?? '');
            }
        }

        $texto = $this->limpiar($texto);

        if ($texto === '') {
            return $this->error('La IA no pudo generar una versión mejorada. Revise el texto e intente nuevamente.', 503);
        }

        return response()->json(['texto_mejorado' => $texto]);
    }

    /** Elimina restos de markdown/comillas y normaliza espacios. */
    private function limpiar(string $texto): string
    {
        $texto = preg_replace('/^```[a-z]*\s*|\s*```$/mi', '', trim($texto)) ?? $texto;
        $texto = str_replace(['**', '__', '`'], '', $texto);
        $texto = preg_replace('/^\s{0,3}#{1,6}\s+/m', '', $texto) ?? $texto;
        $texto = preg_replace('/^\s*[*•]\s+/m', '- ', $texto) ?? $texto;
        $texto = preg_replace("/[ \t]+/", ' ', $texto) ?? $texto;
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto) ?? $texto;

        return trim($texto, " \t\n\r\0\x0B\"“”");
    }

    private function error(string $mensaje, int $estado): JsonResponse
    {
        return response()->json(['message' => $mensaje], $estado);
    }
}
