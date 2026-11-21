import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

/**
 * =============================================================================
 * Prueba de Carga y Estrés con k6 (Hasta 2,000 VUs Concurrentes)
 * Sistema Transaccional de Respuesta Rápida para Empadronamiento en Emergencias
 * Trazabilidad: AC01 (Rendimiento < 2 s), AC03 (Deduplicación Redis SETNX O(1)),
 *               ADR-001 (Cluster Nginx 3 réplicas Laravel), ADR-003 (Laravel Queues)
 * =============================================================================
 */

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1';
const BRIGADISTA_EMAIL = __ENV.BRIGADISTA_EMAIL || 'brigadista@defensacivil.gob.pe';
const BRIGADISTA_PASSWORD = __ENV.BRIGADISTA_PASSWORD || 'Brigadista2026!';
const DUPLICATE_PROBABILITY = 0.15; // 15% de tráfico controlado con DNI duplicado para ejercitar Redis SETNX (409)
const SHARED_DUPLICATE_DNI = '79999999';

// Métricas personalizadas de negocio y arquitectura
const capturasEncoladas202 = new Counter('capturas_encoladas_202');
const duplicadosDetectados409 = new Counter('duplicados_detectados_redis_409');
const errorRateReal = new Rate('error_rate_real');
const capturaDurationTrend = new Trend('captura_duration_ms', true);

// Tanto 200 (login), 202 (encolado exitoso) como 409 (rechazo idempotente por DNI duplicado en SETNX)
// son respuestas HTTP válidas y esperadas del dominio transaccional.
http.setResponseCallback(http.expectedStatuses(200, 202, 409));

export const options = {
    stages: [
        { duration: '20s', target: 100 },  // Fase 1: Calentamiento (100 Brigadistas concurrentes)
        { duration: '30s', target: 500 },  // Fase 2: Carga moderada (500 Brigadistas concurrentes)
        { duration: '40s', target: 1000 }, // Fase 3: Carga alta (1,000 Brigadistas concurrentes)
        { duration: '45s', target: 2000 }, // Fase 4: Pico crítico de emergencia (2,000 Brigadistas concurrentes)
        { duration: '15s', target: 0 },    // Fase 5: Enfriamiento gradual
    ],
    thresholds: {
        // AC01: Tiempo de respuesta percentil 95 menor a 2,000 ms (< 2 s)
        http_req_duration: ['p(95)<2000'],
        captura_duration_ms: ['p(95)<2000'],
        // AC01 / AC04: Tasa de error real de red o servidor (< 1%)
        http_req_failed: ['rate<0.01'],
        error_rate_real: ['rate<0.01'],
    },
};

/**
 * Fase setup(): Autentica al Brigadista una sola vez antes de iniciar la rampa de VUs
 * y distribuye el token Bearer de Laravel Sanctum a todos los Virtual Users (ADR-004).
 */
export function setup() {
    const loginUrl = `${BASE_URL}/api/auth/token`;
    const payload = JSON.stringify({
        email: BRIGADISTA_EMAIL,
        password: BRIGADISTA_PASSWORD,
    });

    const params = {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
        },
    };

    const res = http.post(loginUrl, payload, params);

    const loginExitoso = check(res, {
        'setup: login Sanctum retorna 200 OK': (r) => r.status === 200,
        'setup: respuesta contiene token Bearer': (r) => {
            try {
                return Boolean(r.json('token'));
            } catch (_) {
                return false;
            }
        },
    });

    if (!loginExitoso) {
        throw new Error(`Fallo en setup() al autenticar Brigadista en ${loginUrl}. Status: ${res.status}`);
    }

    return {
        token: res.json('token'),
    };
}

/**
 * Genera un DNI único determinístico de 8 dígitos numéricos por cada VU e iteración.
 */
function generarDniUnico(vuId, iteracion) {
    const secuencial = ((vuId * 100000) + iteracion) % 89999999;
    const numero = 10000000 + secuencial;
    return String(numero).padStart(8, '0').slice(0, 8);
}

/**
 * Escenario principal ejecutado por hasta 2,000 VUs concurrentes contra el balanceador Nginx.
 */
export default function (data) {
    const esIntentoDuplicado = Math.random() < DUPLICATE_PROBABILITY;
    const dni = esIntentoDuplicado
        ? SHARED_DUPLICATE_DNI
        : generarDniUnico(__VU, __ITER);

    const zonaId = ((__VU + __ITER) % 5) + 1;

    const payload = JSON.stringify({
        dni: dni,
        nombres: `Damnificado VU${__VU}`,
        apellidos: `Iteracion ${__ITER}`,
        zona_id: zonaId,
        danos_materiales: 'Afectación estructural severa reportada durante prueba de carga k6 (2000 VUs).',
    });

    const params = {
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${data.token}`,
        },
        tags: {
            endpoint: 'POST /api/captura',
            tipo_dni: esIntentoDuplicado ? 'duplicado_controlado' : 'unico',
        },
    };

    const res = http.post(`${BASE_URL}/api/captura`, payload, params);

    capturaDurationTrend.add(res.timings.duration);

    if (res.status === 202) {
        capturasEncoladas202.add(1);
    } else if (res.status === 409) {
        duplicadosDetectados409.add(1);
    }

    const respuestaValida = check(res, {
        'status es 202 Accepted (nuevo) o 409 Conflict (duplicado SETNX)': (r) =>
            r.status === 202 || r.status === 409,
        'latencia de captura < 2000 ms (AC01)': (r) => r.timings.duration < 2000,
    });

    errorRateReal.add(!respuestaValida);

    sleep(20);
}
