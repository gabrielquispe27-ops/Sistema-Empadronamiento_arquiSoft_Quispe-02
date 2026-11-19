# Análisis de Base de Datos (MySQL 8), Caché/Deduplicación (Redis 7 AOF) y Evidencias de Calidad — Sprint 2

| Metadato | Detalle |
| :--- | :--- |
| **Proyecto** | Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia |
| **Sprint** | Sprint 2 — Persistencia Relacional, Deduplicación en Memoria, Caché y Verificación Automatizada |
| **Drivers y ADRs Evaluados** | `DA01` (Rendimiento), `DA03` (Integridad), `DA04` (Disponibilidad), `AC01`–`AC06`, `ADR-001`–`ADR-004` |

---

## 1. Análisis Técnico de Base de Datos Relacional (`MySQL 8 - InnoDB`)

### 1.1. Segunda Barrera de Integridad: Índice Físico `UNIQUE (dni)`
En escenarios de emergencia donde miles de brigadistas empadronan simultáneamente bajo conectividad inestable (`RC07`), la integridad del padrón consolidado (`AC03`: *0 duplicados persistidos*) no puede depender únicamente de validaciones en la capa de aplicación (`SELECT COUNT(*) WHERE dni = ?`), ya que dos transacciones concurrentes sufrirían condiciones de carrera (*Race Conditions* / *Time-of-Check to Time-of-Use*).

Para garantizar integridad absoluta a nivel de motor de almacenamiento, la migración [2026_10_01_000003_create_damnificados_table.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/database/migrations/2026_10_01_000003_create_damnificados_table.php) define un índice único B-Tree sobre la columna `dni`:

```sql
CREATE TABLE `damnificados` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `dni` CHAR(8) NOT NULL COMMENT 'Clave única física anti-duplicados (RF03, AC03)',
    `nombres` VARCHAR(120) NOT NULL,
    `apellidos` VARCHAR(120) NOT NULL,
    `zona_id` BIGINT UNSIGNED NOT NULL,
    `danos_materiales` TEXT NOT NULL,
    `registrado_por` BIGINT UNSIGNED NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `damnificados_dni_unique` (`dni`),
    CONSTRAINT `damnificados_zona_id_foreign` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- **Uso de `CHAR(8)` de longitud fija:** Al ser el DNI peruano exactamente de 8 dígitos numéricos (`RF02`), el tipo `CHAR(8)` ocupa exactamente 8 bytes en el índice B-Tree sin overhead de prefijo de longitud variable (`VARCHAR`), acelerando las búsquedas puntuales en $O(\log N)$.

### 1.2. Persistencia Idempotente mediante `upsert` (`INSERT ... ON DUPLICATE KEY UPDATE`)
El repositorio [EloquentDamnificadoRepository.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Repositories/EloquentDamnificadoRepository.php) implementa el contrato [DamnificadoRepositoryInterface.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Repositories/Contracts/DamnificadoRepositoryInterface.php) ejecutando una operación atómica `upsert`:

```php
$this->model->newQuery()->upsert(
    [[
        'dni' => (string) $datos['dni'],
        'nombres' => (string) $datos['nombres'],
        'apellidos' => (string) $datos['apellidos'],
        'zona_id' => (int) $datos['zona_id'],
        'danos_materiales' => (string) ($datos['danos_materiales'] ?? ''),
        'registrado_por' => isset($datos['registrado_por']) ? (int) $datos['registrado_por'] : null,
    ]],
    ['dni'],
    ['nombres', 'apellidos', 'zona_id', 'danos_materiales', 'updated_at']
);
```

En MySQL 8, Eloquent compila esta instrucción en una única sentencia nativa:
```sql
INSERT INTO `damnificados` (`dni`, `nombres`, `apellidos`, `zona_id`, `danos_materiales`, `registrado_por`, `created_at`, `updated_at`)
VALUES ('71234567', 'Carlos Alberto', 'Huamán Quispe', 1, 'Colapso parcial...', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    `nombres` = VALUES(`nombres`),
    `apellidos` = VALUES(`apellidos`),
    `zona_id` = VALUES(`zona_id`),
    `danos_materiales` = VALUES(`danos_materiales`),
    `updated_at` = VALUES(`updated_at`);
```

**Ventajas arquitectónicas:**
1. **Idempotencia ante reintentos de la cola (`$tries = 3`):** Si un worker procesa un job y sufre un corte de red justo después del commit, el reintento del job no fallará con excepción `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`, sino que actualizará idempotentemente el mismo registro.
2. **Cero duplicados tras expiración del TTL de Redis:** Si un mismo DNI vuelve a enviarse después de las 24 horas de TTL en Redis, MySQL consolida el registro existente sin duplicar filas.

### 1.3. Protección del Pool de Conexiones de MySQL mediante Workers Asíncronos (`ADR-003`)
Si 2,000 brigadistas ejecutaran `INSERT` síncronos contra MySQL desde las peticiones HTTP:
- Se agotarían las conexiones máximas del motor (`max_connections = 151` por defecto en MySQL 8).
- Aumentaría la contención de bloqueos de índice (*gap locks / row locks*) en `InnoDB`.

Al desacoplar la escritura mediante [RegistrarDamnificadoJob.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Jobs/RegistrarDamnificadoJob.php) en la cola `empadronamiento`, el número de escrituras concurrentes contra MySQL queda estrictamente acotado al número de procesos *Worker* configurados ($M$ workers), transformando picos abruptos de 2,000 VUs en un flujo laminar constante que vacía el backlog en $< 10\text{ min}$ (`AC01`).

---

## 2. Estrategia de Deduplicación y Caché en Memoria (`Redis 7 + AOF`)

### 2.1. Primera Barrera Anti-Duplicados: Reserva Atómica $O(1)$ con `SETNX`
En [RedisDeduplicacionCache.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Repositories/RedisDeduplicacionCache.php), cada solicitud de captura ejecuta:

```text
SETNX dni:71234567 "1"
EXPIRE dni:71234567 86400
```

- **Complejidad Temporal $O(1)$:** Redis procesa `SETNX` en un único hilo (*single-threaded event loop*) en memoria RAM en menos de $0.2\text{ ms}$, haciendo imposible que dos peticiones simultáneas con el mismo DNI obtengan `1` al mismo tiempo.
- **Descarte Temprano (*Fail-Fast*):** Cuando un segundo brigadista envía un DNI ya reservado, `SETNX` devuelve `0` y [EmpadronamientoController.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Http/Controllers/EmpadronamientoController.php) responde `409 Conflict` inmediatamente, ahorrando serialización de jobs en la cola y operaciones de E/S en MySQL.

### 2.2. Durabilidad de Colas y Bloqueos con Persistencia `AOF` (`infra/redis/redis.conf`)
Para cumplir el escenario de disponibilidad `AC04` (*0 jobs perdidos ante reinicio*), [infra/redis/redis.conf](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/infra/redis/redis.conf) habilita:
- `appendonly yes` y `appendfsync everysec`: Registra cada operación de escritura (`SETNX`, `RPUSH` de la cola `empadronamiento`) en el log AOF con `fsync` cada segundo y preámbulo híbrido RDB (`aof-use-rdb-preamble yes`).
- `maxmemory-policy noeviction`: Impide que Redis desaloje llaves de la cola `queues:empadronamiento` o reservas `dni:*` bajo presión de memoria.

### 2.3. Caché de Reportes Estadísticos por Zona (`Cache-Aside` en `ReporteService`)
El servicio [ReporteService.php](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/app/Services/ReporteService.php) aplica el patrón **Cache-Aside** sobre la llave `reportes:metricas_por_zona` con un `TTL = 60 segundos`:

| Escenario de Consulta (`GET /api/reportes`) | Operación Ejecutada | Complejidad | Latencia Promedio Estimada | Impacto en MySQL 8 |
| :--- | :--- | :---: | :---: | :--- |
| **Sin Caché (Cache Miss / 1ra petición)** | `SELECT zonas.*, (SELECT COUNT(*) FROM damnificados WHERE zona_id = zonas.id) ...` | $O(Z \cdot \log D)$ | `18 ms – 65 ms` | 1 consulta agregada `WITH COUNT` por petición |
| **Con Caché Redis (Cache Hit durante 60 s)** | `GET empadronamiento_cache_reportes:metricas_por_zona` (Redis DB 1) | $O(1)$ | **`0.4 ms – 1.8 ms`** | **0 consultas a MySQL** (reducción del 97% en latencia) |

---

## 3. Plantilla de Evidencias de Verificación y Calidad (Sprint 2)

> [!TIP]
> Utiliza las siguientes secciones estructuradas para adjuntar las capturas de pantalla o salidas de consola reales obtenidas durante la ejecución de la suite de calidad.

---

### 3.1. Evidencia 1: Pruebas Automatizadas Unitarias y Funcionales + Cobertura (`PHPUnit 11`)

- **Comando ejecutado:**
  ```bash
  vendor/bin/phpunit --coverage-text
  ```
- **Suites verificadas:**
  - `Tests\Unit\EmpadronamientoServiceTest` (3 tests: reserva atómica `SETNX` -> encola job, rechazo duplicado, validación DNI).
  - `Tests\Unit\RegistrarDamnificadoJobTest` (2 tests: `upsertPadron` idempotente en repositorio inyectado, log en `failed()`).
  - `Tests\Feature\AuthTest` (3 tests: emisión de token Sanctum `200`, credenciales inválidas `422`, validación `422`).
  - `Tests\Feature\CapturaTest` (4 tests: sin token `401`, captura válida `202 Accepted`, DNI duplicado `409 Conflict`, payload inválido `422`).
  - `Tests\Feature\PadronTest` (3 tests: sin token `401`, rol brigadista bloqueado `403`, coordinador consulta y filtra por zona `200`).
  - `Tests\Feature\ReporteTest` (3 tests: sin token `401`, rol brigadista bloqueado `403`, coordinador obtiene métricas y valida caché `200`).

#### 📸 Salida de Consola / Captura de PHPUnit + Coverage:
```text
[PEGAR AQUÍ LA SALIDA DE CONSOLA DE: vendor/bin/phpunit --coverage-text]
```
<!-- Si deseas adjuntar imagen: ![Evidencia PHPUnit Coverage](../../img/evidencia-phpunit-sprint2.png) -->

---

### 3.2. Evidencia 2: Documentación Interactiva OpenAPI 3.0 (`L5-Swagger`)

- **Comando de generación:**
  ```bash
  php artisan l5-swagger:generate
  ```
- **Archivo generado:** `storage/api-docs/api-docs.json`
- **URL del Portal Swagger UI:** `http://127.0.0.1:8000/api/documentation`
- **Endpoints y códigos documentados:**
  - `POST /api/auth/token` (`200`, `400`, `401`, `422`)
  - `POST /api/captura` (`202 Accepted`, `400`, `401`, `409 Conflict`, `422`)
  - `GET /api/padron` (`200`, `401`, `403`)
  - `GET /api/reportes` (`200`, `401`, `403`)

#### 📸 Captura de Pantalla de Swagger UI (`/api/documentation`):
```text
[INSERTAR AQUÍ CAPTURA DE SWAGGER UI MOSTRANDO LOS 4 ENDPOINTS Y EL ESQUEMA BEARER SANCTUM]
```
<!-- Ejemplo: ![Evidencia Swagger UI](../../img/evidencia-swagger-sprint2.png) -->

---

### 3.3. Evidencia 3: Pruebas de Integración de Endpoints con Colección Postman

- **Colección importada:** [tests/postman/empadronamiento-sprint2.postman_collection.json](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/tests/postman/empadronamiento-sprint2.postman_collection.json)
- **Casos verificados con `pm.test()`:**
  1. `POST /api/auth/token` → `200 OK` (Guarda automáticamente `{{token}}`).
  2. `POST /api/captura` (DNI Nuevo) → `202 Accepted` (`status: "queued"`, latencia `< 2000 ms`).
  3. `POST /api/captura` (Mismo DNI) → `409 Conflict` (`error_code: "DUPLICATE_DNI"`).
  4. `POST /api/captura` (DNI Inválido) → `422 Unprocessable Entity`.
  5. `GET /api/padron?zona_id=1` → `200 OK` (Padrón paginado sin duplicados).
  6. `GET /api/reportes` → `200 OK` (Métricas y porcentaje de cobertura por zona).

#### 📸 Captura del Collection Runner de Postman / Salida de Newman:
```text
[INSERTAR AQUÍ CAPTURA DEL COLLECTION RUNNER DE POSTMAN CON LOS TESTS EN VERDE (PASSED)]
```
<!-- Ejemplo: ![Evidencia Postman Runner](../../img/evidencia-postman-sprint2.png) -->

---

### 3.4. Evidencia 4: Pruebas de Carga y Estrés con `k6` (Hasta 2,000 VUs Concurrentes)

- **Script ejecutado:** [k6/load-test-empadronamiento.js](file:///C:/Users/lenovo%201/Documents/Proyecto_fina_arquitectura/Sistema-Empadronamiento_arquiSoft_Quispe-02/k6/load-test-empadronamiento.js)
- **Comando de ejecución:**
  ```bash
  k6 run -e BASE_URL=http://127.0.0.1 k6/load-test-empadronamiento.js
  ```
- **Perfil de carga (`stages`):** Rampa progresiva `100 VUs (20s)` → `500 VUs (30s)` → `1,000 VUs (40s)` → `2,000 VUs (45s)` → `0 VUs (15s)`.
- **Criterios de Aceptación Evaluados (`AC01` y `AC03`):**

| Métrica k6 | Umbral Definido (`Threshold`) | Valor Obtenido en Prueba | Estado (`PASS` / `FAIL`) |
| :--- | :---: | :---: | :---: |
| `http_req_duration` (`p(95)`) | `< 2000 ms` (`< 2 s`) | `[COMPLETAR ms]` | `[PASS]` |
| `captura_duration_ms` (`p(95)`) | `< 2000 ms` (`< 2 s`) | `[COMPLETAR ms]` | `[PASS]` |
| `http_req_failed` | `rate < 0.01` (`< 1%`) | `[COMPLETAR %]` | `[PASS]` |
| `error_rate_real` | `rate < 0.01` (`< 1%`) | `[COMPLETAR %]` | `[PASS]` |
| `capturas_encoladas_202` | Conteo total `202 Accepted` | `[COMPLETAR N]` | `[OK]` |
| `duplicados_detectados_redis_409` | Conteo total `409 Conflict` | `[COMPLETAR N]` | `[OK]` |

#### 📸 Salida de Consola / Resumen de Ejecución de `k6`:
```text
[PEGAR AQUÍ EL RESUMEN FINAL DE CONSOLA DE: k6 run k6/load-test-empadronamiento.js]
```
<!-- Ejemplo: ![Evidencia k6 2000 VUs](../../img/evidencia-k6-2000vus.png) -->
