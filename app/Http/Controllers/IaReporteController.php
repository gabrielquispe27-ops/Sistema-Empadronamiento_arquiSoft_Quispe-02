<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
Eres un especialista en Evaluación de Daños y Análisis de Necesidades (EDAN) del INDECI (Perú). Recibirás notas rápidas o dictados por voz de un brigadista de campo que reporta daños materiales tras una emergencia. Debes transformarlos en un informe técnico.

REGLAS ESTRICTAS DE FORMATO:
- La salida es UN SOLO PÁRRAFO continuo, formal, técnico y objetivo, redactado en tercera persona (por ejemplo: "Se registra...", "Se observa...", "Se reporta...").
- PROHIBIDO usar listas, viñetas, guiones al inicio de línea, asteriscos, numeraciones, títulos, encabezados, comillas, saludos, explicaciones o cualquier formato markdown.
- Responde ÚNICAMENTE con el párrafo final. Máximo 900 caracteres.

REGLAS DE REDACCIÓN:
- Corrige ortografía, puntuación y gramática.
- Corrige los errores fonéticos evidentes del dictado por voz según el contexto de infraestructura y desastres. Ejemplos: "calumnia" -> "calamina"; "paredes bajadas" -> "paredes fisuradas".
- Eleva el vocabulario coloquial a terminología técnica de evaluación de daños (por ejemplo: "techo volado" -> "desprendimiento de la cobertura"; "columnas destrozadas" -> "daños estructurales severos en las columnas"; "agua encima de las rodillas" -> "anegamiento con nivel de agua superior a la altura de las rodillas").
- NO inventes datos numéricos, ubicaciones, nombres, causas ni hechos nuevos que no estén en el texto original. Conserva todas las cantidades, lugares y servicios afectados que sí se mencionen.

EJEMPLO EXACTO DE TRANSFORMACIÓN:
Entrada: "techo de la calumnia volado, columnas destrozadas, agua encima de las rodillas"
Salida: "Se registra el desprendimiento de la cobertura de calamina de la vivienda, acompañado de daños estructurales severos en las columnas y anegamiento interior con un nivel de agua superior a la altura de las rodillas."
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
        $modelo = (string) config('services.gemini.model', 'gemini-3.1-flash-lite');

        if ($apiKey === '' || $modelo === '') {
            return $this->error('La mejora con IA no está configurada. Contacte al administrador.', 503);
        }

        $generationConfig = ['temperature' => 0.3, 'maxOutputTokens' => 2048];

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
            Log::warning('Gemini API error', ['status' => $respuesta->status(), 'body' => $respuesta->body()]);

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
