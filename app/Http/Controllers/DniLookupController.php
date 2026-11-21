<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Controlador invocable DniLookupController (Componente CONTROLADOR).
 *
 * Consulta datos de identidad por DNI (8 dígitos) priorizando caché Redis
 * (clave reniec_dni:{dni}, TTL 30 días) y recurriendo al servicio externo
 * Decolecta RENIEC con degradación controlada ante fallos de red.
 */
class DniLookupController extends Controller
{
    private const CACHE_TTL_DAYS = 30;

    public function __invoke(string $dni): JsonResponse
    {
        if (preg_match('/^\d{8}$/', $dni) !== 1) {
            return response()->json([
                'ok' => false,
                'message' => 'El DNI debe contener exactamente 8 dígitos numéricos.',
            ], 422);
        }

        $cacheKey = "reniec_dni:{$dni}";

        try {
            $cached = Cache::store('redis')->get($cacheKey);
            if (is_array($cached) && ! empty($cached['nombres']) && ! empty($cached['apellidos'])) {
                return response()->json([
                    'ok' => true,
                    'nombres' => (string) $cached['nombres'],
                    'apellidos' => (string) $cached['apellidos'],
                ]);
            }
        } catch (Throwable) {
            // Si Redis no está disponible, continuar con la consulta HTTP sin romper la aplicación.
        }

        $token = (string) config('services.reniec.token', '');

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(4)
                ->get("https://api.decolecta.com/v1/reniec/dni?numero={$dni}");

            if (! $response->successful()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se pudo obtener información del DNI en RENIEC.',
                ]);
            }

            $data = $response->json();
            if (! is_array($data)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Respuesta inválida del servicio RENIEC.',
                ]);
            }

            $nombres = trim((string) ($data['first_name'] ?? $data['nombres'] ?? ''));

            $apellidoPaterno = trim((string) ($data['first_last_name'] ?? $data['apellidoPaterno'] ?? $data['apellido_paterno'] ?? ''));
            $apellidoMaterno = trim((string) ($data['second_last_name'] ?? $data['apellidoMaterno'] ?? $data['apellido_materno'] ?? ''));
            $apellidos = trim("{$apellidoPaterno} {$apellidoMaterno}");

            if ($apellidos === '' && isset($data['apellidos'])) {
                $apellidos = trim((string) $data['apellidos']);
            }

            if ($nombres === '' || $apellidos === '') {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se encontraron nombres y apellidos para el DNI consultado.',
                ]);
            }

            $payload = [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
            ];

            try {
                Cache::store('redis')->put($cacheKey, $payload, now()->addDays(self::CACHE_TTL_DAYS));
            } catch (Throwable) {
                // Ignorar error de escritura en caché para no interrumpir la respuesta al cliente.
            }

            return response()->json([
                'ok' => true,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
            ]);
        } catch (Throwable) {
            return response()->json([
                'ok' => false,
                'message' => 'Servicio RENIEC no disponible temporalmente. Ingrese los datos manualmente.',
            ]);
        }
    }
}
