# Diseño Interno de Módulos (Patrón MVC)

Detalle interno de los **cuatro módulos del sistema** bajo el patrón arquitectónico **Modelo-Vista-Controlador (MVC) con Capa de Servicios**, especificando cómo se separan y comunican los componentes **Controlador**, **Modelo** (Servicios, Jobs y Modelos Eloquent) y **Vista** en cada módulo.

## 1. Módulo Autenticación (`Auth`)

| Componente MVC | Elemento | Archivo | Responsabilidad |
| :--- | :--- | :--- | :--- |
| **Controlador** | Controller | `app/Http/Controllers/AuthController.php` | Orquesta `showLoginForm()`, `login()` y `logout()`. |
| **Controlador** | FormRequest | `app/Http/Requests/LoginRequest.php` | Valida el formato de `email` y `password`. |
| **Modelo (Negocio)** | Service | `app/Services/AuthService.php` | Valida credenciales y emite/revoca tokens de Laravel Sanctum (`ADR-004`). |
| **Modelo (Datos)** | Model Eloquent | `app/Models/User.php` | Entidad de usuario (`users`) con roles (`brigadista`, `coordinador`) y `HasApiTokens`. |
| **Vista** | Plantilla Blade | `resources/views/auth/login.blade.php` | Formulario de inicio de sesión (extiende `layouts/app.blade.php`). |

## 2. Módulo Captura / Empadronamiento

| Componente MVC | Elemento | Archivo | Responsabilidad |
| :--- | :--- | :--- | :--- |
| **Controlador** | Controller | `app/Http/Controllers/EmpadronamientoController.php` | Muestra el formulario (`create()`) y recibe el envío de campo (`store()`). |
| **Controlador** | FormRequest | `app/Http/Requests/StoreDamnificadoRequest.php` | Valida DNI de 8 dígitos, datos demográficos, `zona_id` y `nivel_dano`. |
| **Modelo (Contrato DIP)** | Interface | `app/Services/Contracts/EmpadronamientoServiceInterface.php` | Contrato que desacopla al controlador de la implementación concreta (`ADR-002`). |
| **Modelo (Negocio)** | Service | `app/Services/EmpadronamientoService.php` | Deduplica atómicamente el DNI en Redis (`SETNX`) y despacha el DTO a la cola. |
| **Modelo (Cola)** | Job | `app/Jobs/RegistrarDamnificadoJob.php` | Unidad asíncrona procesada por el Worker (`queue:work`) con reintentos y backoff. |
| **Modelo (Datos)** | Model Eloquent | `app/Models/Damnificado.php` | Entidad del padrón (`damnificados`) con método idempotente `upsertPadron()`. |
| **Vista** | Plantilla Blade | `resources/views/captura/create.blade.php` | Formulario *Mobile-First* con Bootstrap 5 y respaldo en `localStorage` (`AC02`, `AC04`). |

## 3. Módulo Padrón (Dashboard de Consolidación)

| Componente MVC | Elemento | Archivo | Responsabilidad |
| :--- | :--- | :--- | :--- |
| **Controlador** | Controller | `app/Http/Controllers/PadronController.php` | Recibe el filtro opcional `zona_id`, consulta al servicio y renderiza el dashboard (`index()`). |
| **Controlador** | Middleware | `app/Http/Middleware/EnsureRoleMiddleware.php` | Restringe el acceso exclusivamente al rol `coordinador`. |
| **Modelo (Negocio)** | Service | `app/Services/PadronService.php` | Ejecuta la consulta paginada del padrón consolidado y lista las zonas con conteo de afectados. |
| **Modelo (Datos)** | Models Eloquent | `app/Models/Damnificado.php`, `app/Models/Zona.php` | Entidades `Damnificado` y `Zona` relacionadas (`1:N`) para consolidar el padrón único. |
| **Vista** | Plantilla Blade | `resources/views/dashboard/index.blade.php` | Tabla paginada del padrón consolidado y selector de filtrado por zona de emergencia. |

## 4. Módulo Reportes (Métricas y Cobertura)

| Componente MVC | Elemento | Archivo | Responsabilidad |
| :--- | :--- | :--- | :--- |
| **Controlador** | Controller | `app/Http/Controllers/ReporteController.php` | Solicita las métricas consolidadas al servicio y renderiza el reporte estadístico (`index()`). |
| **Controlador** | Middleware | `app/Http/Middleware/EnsureRoleMiddleware.php` | Restringe el acceso exclusivamente al rol `coordinador`. |
| **Modelo (Negocio)** | Service | `app/Services/ReporteService.php` | Calcula empadronados y porcentaje de cobertura por zona utilizando `Cache::remember` en Redis. |
| **Modelo (Datos)** | Models Eloquent | `app/Models/Zona.php`, `app/Models/Damnificado.php` | Consulta agregada `Zona::withCount('damnificados')` sobre el esquema relacional. |
| **Vista** | Plantilla Blade | `resources/views/reportes/index.blade.php` | Tarjetas KPI y tabla de progreso con barras de porcentaje de cobertura por zona. |

## 5. Esquema de datos base (MySQL)

| Tabla | Migración | Clave | Relaciones |
| :--- | :--- | :--- | :--- |
| `users` | `2026_10_01_000001_create_users_table.php` | PK `id`, UK `email` | Posee tokens de Sanctum; registra `damnificados`. |
| `zonas` | `2026_10_01_000002_create_zonas_table.php` | PK `id`, UK `codigo` | Tiene muchos `damnificados` (`1:N`). |
| `damnificados` | `2026_10_01_000003_create_damnificados_table.php` | PK `id`, **UK `dni`** | Pertenece a una `zona` (`zona_id`) y a un brigadista (`registrado_por`). |

> La clave única sobre `dni` es el soporte físico de la regla de negocio anti-duplicados (`RF03`), junto con la validación atómica previa en Redis (`SETNX`) dentro de `EmpadronamientoService`.

## 6. Reglas de diseño interno MVC

1. **Separación Controlador → Modelo:** Ningún controller puede consultar Eloquent directamente; siempre delega en el `Service` de su módulo.
2. **Desacoplamiento entre Módulos:** Un `Service` no expone modelos internos a otros módulos; la coordinación se realiza en el controlador o mediante jobs/eventos.
3. **Jobs Delgados:** `RegistrarDamnificadoJob` contiene únicamente la orquestación asíncrona y delega la persistencia idempotente en `Damnificado::upsertPadron()`.
4. **Vistas Declarativas:** Las plantillas Blade en `resources/views/` solo reciben datos ya procesados por el controlador y nunca ejecutan lógica de dominio ni consultas SQL.
