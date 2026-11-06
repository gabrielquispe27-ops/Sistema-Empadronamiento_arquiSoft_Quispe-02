# Patrón y Estilos Arquitectónicos

## 1. Taxonomía de Estilos y Patrón Arquitectónico Aplicado

En la ingeniería de sistemas transaccionales modernos, una arquitectura robusta combina de forma jerárquica **estilos arquitectónicos macro** (nivel de topología y particionamiento) con un **patrón arquitectónico de aplicación** (nivel de organización del código fuente).

Para el **Sistema Transaccional de Respuesta Rápida para el Empadronamiento**, la arquitectura se define mediante la convergencia de cuatro niveles estructurales:

| Nivel Arquitectónico | Estilo / Patrón Seleccionado | Decisión / Driver | Rol Estructural en el Sistema |
| :--- | :--- | :--- | :--- |
| **1. Estilo de Red (Distribución)** | **Cliente-Servidor (*Client-Server*)** | `DA02`, `RC01` | Separa el cliente web ligero (navegador móvil/escritorio con HTML5, Bootstrap 5 y respaldo `localStorage`) del clúster de servidores Laravel presidido por **Nginx**. |
| **2. Estilo de Despliegue y Particionamiento** | **Monolito Modular (*Modular Monolith*)** | `ADR-001`, `DA04` | Empaqueta toda la base de código en una unidad cohesiva particionada en **4 módulos funcionales** (`Auth`, `Captura/Empadronamiento`, `Padrón` y `Reportes`), desplegada en contenedores especializados (`App Web` + `Workers`). |
| **3. Patrón Arquitectónico Interno (Código)** | **Modelo-Vista-Controlador (MVC) en Capas con Servicios** | `ADR-002`, `DA06` | Divide cada módulo en **Controlador** (`routes/`, `app/Http/`), **Vista** (`resources/views/`) y **Modelo** subdividido en **Lógica de Negocio** (`app/Services/`), **Cola Asíncrona** (`app/Jobs/`) y **Persistencia** (`app/Models/`, `database/`). |
| **4. Estilo de Concurrencia e Integración** | **Productor-Consumidor Basado en Colas (*Queue-Centric*)** | `ADR-003`, `DA01` | Desacopla la recepción HTTP masiva (productor `EmpadronamientoService`) de la inserción relacional en MySQL (consumidor `RegistrarDamnificadoJob` en procesos `queue:work`) a través de **Redis 7 (AOF)**. |

---

## 2. Diagrama de la Arquitectura (Vista Estructural MVC)

```mermaid
flowchart TD
    %% ESTILOS VISUALES DEL PROYECTO
    classDef actor fill:#f9f9f9,stroke:#333,stroke-width:1.5px,color:#111;
    classDef frontend fill:#ffffff,stroke:#333,stroke-width:1.5px,color:#111;
    classDef ctrl fill:#e6ffe6,stroke:#2ca02c,stroke-width:1.5px,color:#111;
    classDef mw fill:#f2e6ff,stroke:#9966cc,stroke-width:1.5px,color:#111;
    classDef view fill:#e6f2ff,stroke:#007acc,stroke-width:1.5px,color:#111;
    classDef service fill:#fff2e6,stroke:#ff9933,stroke-width:1.5px,color:#111;
    classDef contract fill:#fff9e6,stroke:#d4a017,stroke-width:1.5px,stroke-dasharray: 5 5,color:#111;
    classDef job fill:#fce4ec,stroke:#c2185b,stroke-width:1.5px,color:#111;
    classDef model fill:#e8eaf6,stroke:#3f51b5,stroke-width:1.5px,color:#111;
    classDef queue fill:#fde8e8,stroke:#d9534f,stroke-width:1.5px,color:#111;
    classDef db fill:#e8f8e8,stroke:#2d882d,stroke-width:1.5px,color:#111;

    %% ACTORES Y CLIENTE WEB
    Brigadista["«actor»\nBrigadista en Campo"]:::actor
    Coordinador["«actor»\nCoordinador de Defensa Civil"]:::actor

    subgraph FRONTEND ["Cliente Web (Navegador Móvil / Escritorio)"]
        UI["HTML5 + Bootstrap 5 Mobile-First + JS (localStorage)\n[Formularios de Campo y Dashboard Analítico]"]:::frontend
    end

    Brigadista --> UI
    Coordinador --> UI

    %% MONOLITO MODULAR LARAVEL BAJO PATRÓN MVC
    subgraph BACKEND ["«monolito modular» Sistema de Empadronamiento [PHP 8.x / Laravel — Patrón MVC + Capa de Servicios]"]
        direction TB

        %% ==========================================
        %% 1. COMPONENTE CONTROLADOR (C)
        %% ==========================================
        subgraph COMP_CONTROLADOR ["COMPONENTE CONTROLADOR (Capa de Control HTTP — routes/ y app/Http/)"]
            direction TB

            MW["«enrutamiento + middlewares + form requests»\nroutes/web.php | routes/api.php\nauth:sanctum | EnsureRoleMiddleware (brigadista, coordinador)\nLoginRequest | StoreDamnificadoRequest"]:::mw

            subgraph CONTROLLERS ["Controladores por Módulo (app/Http/Controllers/ — Sin lógica de negocio)"]
                direction LR
                C_Auth["«controller»\nMódulo Auth\nAuthController\nshowLoginForm() | login() | logout()"]:::ctrl
                C_Cap["«controller»\nMódulo Captura\nEmpadronamientoController\ncreate() | store()"]:::ctrl
                C_Pad["«controller»\nMódulo Padrón\nPadronController\nindex()"]:::ctrl
                C_Rep["«controller»\nMódulo Reportes\nReporteController\nindex()"]:::ctrl
            end

            MW --> C_Auth
            MW --> C_Cap
            MW --> C_Pad
            MW --> C_Rep
        end

        %% ==========================================
        %% 2. COMPONENTE VISTA (V)
        %% ==========================================
        subgraph COMP_VISTA ["COMPONENTE VISTA (Capa de Presentación SSR — resources/views/)"]
            direction TB

            V_Layout["«layout base»\nlayouts/app.blade.php\n(Plantilla maestra Bootstrap 5 + barra de navegación por rol)"]:::view

            subgraph VISTAS_MODULOS ["Vistas Blade por Módulo (Renderizadas en Servidor)"]
                direction LR
                V_Auth["«vista»\nMódulo Auth\nauth/login.blade.php\n(Formulario de acceso)"]:::view
                V_Cap["«vista»\nMódulo Captura\ncaptura/create.blade.php\n(Formulario Mobile-First + localStorage)"]:::view
                V_Dash["«vista»\nMódulo Padrón\ndashboard/index.blade.php\n(Padrón paginado + filtro por zona)"]:::view
                V_Rep["«vista»\nMódulo Reportes\nreportes/index.blade.php\n(KPIs y cobertura por zona)"]:::view
            end

            V_Auth -. "extiende (@extends)" .-> V_Layout
            V_Cap -. "extiende (@extends)" .-> V_Layout
            V_Dash -. "extiende (@extends)" .-> V_Layout
            V_Rep -. "extiende (@extends)" .-> V_Layout
        end

        %% ==========================================
        %% 3. COMPONENTE MODELO (M)
        %% ==========================================
        subgraph COMP_MODELO ["COMPONENTE MODELO (Lógica de Negocio, Procesamiento Asíncrono y Persistencia — app/Services/, app/Jobs/, app/Models/, database/)"]
            direction TB

            subgraph SUB_SERVICIOS ["3.1 Subcapa de Lógica de Negocio y Dominio (app/Services/)"]
                direction LR
                S_Auth["«service»\nMódulo Auth\nAuthService\nlogin() | logout()\n(Tokens Laravel Sanctum)"]:::service
                I_Cap["«interface (DIP)»\nEmpadronamientoServiceInterface\nregistrar(array $datos): bool"]:::contract
                S_Cap["«service»\nMódulo Captura\nEmpadronamientoService\nregistrar()\n(SETNX DNI + dispatch Job)"]:::service
                S_Pad["«service»\nMódulo Padrón\nPadronService\nobtenerPadronConsolidado()\nlistarZonasActivas()"]:::service
                S_Rep["«service»\nMódulo Reportes\nReporteService\nobtenerMetricasPorZona()\n(Cache::remember 60s)"]:::service
            end

            subgraph SUB_COLA ["3.2 Subcapa de Procesamiento Asíncrono (app/Jobs/ — Contenedor Worker queue:work)"]
                direction LR
                J_Cap["«job»\nMódulo Captura\nRegistrarDamnificadoJob\nhandle(): consume DTO de Redis\n(3 tries, backoff [5, 15, 30]s)"]:::job
            end

            subgraph SUB_DATOS ["3.3 Subcapa de Entidades Eloquent ORM y Esquema (app/Models/ y database/migrations/)"]
                direction LR
                M_User["«model»\nUser\n(Tabla: users)\nHasApiTokens | esBrigadista() | esCoordinador()"]:::model
                M_Dam["«model»\nDamnificado\n(Tabla: damnificados — UNIQUE dni)\nupsertPadron() | zona() | brigadista()"]:::model
                M_Zona["«model»\nZona\n(Tabla: zonas)\ndamnificados() (1:N) | poblacion_estimada"]:::model
                MI["«migrations»\n2026_10_01_000001_create_users_table\n2026_10_01_000002_create_zonas_table\n2026_10_01_000003_create_damnificados_table"]:::model
            end
        end

        %% RELACIONES ENTRE CONTROLADOR Y VISTA
        UI -- "1. Petición HTTPS" --> MW
        C_Auth -. "renderiza vista" .-> V_Auth
        C_Cap -. "renderiza vista" .-> V_Cap
        C_Pad -. "renderiza vista + datos" .-> V_Dash
        C_Rep -. "renderiza vista + métricas" .-> V_Rep

        %% RELACIONES ENTRE CONTROLADOR Y MODELO (SERVICIOS)
        C_Auth -- "delega login/logout" --> S_Auth
        C_Cap -- "depende de abstracción (DIP)" --> I_Cap
        S_Cap -. "implementa (AppServiceProvider)" .-> I_Cap
        C_Pad -- "consulta padrón y zonas" --> S_Pad
        C_Rep -- "solicita métricas" --> S_Rep

        %% RELACIONES INTERNAS DEL COMPONENTE MODELO
        S_Auth -- "autentica y emite token" --> M_User
        J_Cap -- "ejecuta upsertPadron()" --> M_Dam
        S_Pad -- "paginate() + with('zona')" --> M_Dam
        S_Pad -- "withCount('damnificados')" --> M_Zona
        S_Rep -- "withCount('damnificados')" --> M_Zona
        M_Dam -- "pertenece a (zona_id)" --> M_Zona
        M_User --> MI
        M_Dam --> MI
        M_Zona --> MI
    end

    %% INFRAESTRUCTURA DE DATOS Y COLAS EXTERNA AL PROCESO WEB
    REDIS[("«broker y caché en memoria»\nRedis 7 (AOF)\n• Cola: empadronamiento\n• Deduplicación: SETNX empadronamiento:dni:{dni}\n• Caché: reportes:metricas_por_zona")]:::queue
    DB[("«base de datos relacional»\nMySQL 8 (padron_db)\n• users\n• zonas\n• damnificados (UNIQUE dni)")]:::db

    %% CONEXIONES CON REDIS Y MYSQL
    S_Cap -- "SETNX dni + dispatch(Job)\n(TCP 6379 — < 5 ms)" --> REDIS
    REDIS -- "BLPOP job asíncrono\n(TCP 6379)" --> J_Cap
    S_Rep -- "Cache::remember()\n(TCP 6379)" --> REDIS

    M_User -- "SQL (TCP 3306)" --> DB
    M_Dam -- "SQL UPSERT / SELECT (TCP 3306)" --> DB
    M_Zona -- "SQL SELECT (TCP 3306)" --> DB
```

---

## 3. Matriz de Separación y Reglas del Patrón MVC

Para garantizar el atributo de **Mantenibilidad (`AC06`, `DA06`)**, cada componente del patrón MVC cumple reglas estrictas de responsabilidad y acoplamiento:

| Componente MVC | Ubicación en el Repositorio | Artefactos Implementados | Dependencias Permitidas | Dependencias Prohibidas |
| :--- | :--- | :--- | :--- | :--- |
| **CONTROLADOR (`C`)**<br>*(Capa de Control HTTP)* | `routes/`<br>`app/Http/Controllers/`<br>`app/Http/Middleware/`<br>`app/Http/Requests/` | • `web.php`, `api.php`<br>• `EnsureRoleMiddleware`<br>• `LoginRequest`, `StoreDamnificadoRequest`<br>• `AuthController`, `EmpadronamientoController`, `PadronController`, `ReporteController` | • `EmpadronamientoServiceInterface` (en módulo Captura)<br>• `AuthService`, `PadronService`, `ReporteService`<br>• Vistas Blade (`view(...)`) | ❌ Consultas directas a Eloquent (`User`, `Damnificado`, `Zona`)<br>❌ Llamadas directas a `Redis` o despacho directo de `Jobs`<br>❌ Cálculos estadísticos o reglas de negocio |
| **VISTA (`V`)**<br>*(Capa de Presentación SSR)* | `resources/views/` | • `layouts/app.blade.php`<br>• `auth/login.blade.php`<br>• `captura/create.blade.php`<br>• `dashboard/index.blade.php`<br>• `reportes/index.blade.php` | • Variables/DTOs inyectados por el Controlador<br>• Plantilla maestra (`@extends('layouts.app')`)<br>• API `localStorage` del navegador | ❌ Invocar `Services` o `Models` desde directivas `@php`<br>❌ Ejecutar consultas SQL o lógica de validación de dominio |
| **MODELO (`M` — Negocio)**<br>*(Subcapa de Servicios)* | `app/Services/`<br>`app/Providers/` | • `EmpadronamientoServiceInterface`<br>• `AuthService`, `EmpadronamientoService`, `PadronService`, `ReporteService`<br>• `AppServiceProvider` | • Modelos Eloquent (`User`, `Damnificado`, `Zona`)<br>• `Redis` (`SETNX`, `Cache::remember`)<br>• Despacho de `RegistrarDamnificadoJob` | ❌ Objetos HTTP (`Request`, `RedirectResponse`, `Session`)<br>❌ Renderizado de vistas Blade |
| **MODELO (`M` — Cola)**<br>*(Subcapa Asíncrona)* | `app/Jobs/` | • `RegistrarDamnificadoJob` | • Modelo `Damnificado::upsertPadron()`<br>• `Redis::del()` (en `failed()` para liberar DNI) | ❌ `EmpadronamientoService` (evita ciclos de dependencia)<br>❌ Capa HTTP o Vistas |
| **MODELO (`M` — Datos)**<br>*(Subcapa de Persistencia)* | `app/Models/`<br>`database/migrations/` | • `User`, `Damnificado`, `Zona`<br>• 3 migraciones relacionales (`users`, `zonas`, `damnificados`) | • Motor relacional **MySQL 8 (`padron_db`)** y traits de Eloquent/Sanctum | ❌ `Controllers`, `Services`, `Jobs` o `Vistas` |

---

## 4. Evaluación Comparativa frente a Alternativas Descartadas

La elección del **Monolito Modular en Capas (MVC + Services + Queues)** se justifica al contrastarla con otras alternativas arquitectónicas evaluadas para el proyecto:

| Alternativa Evaluada | Ventajas | Por qué fue descartada / seleccionada frente a los Drivers (`DA01`–`DA06`) | Decisión |
| :--- | :--- | :--- | :--- |
| **A. Monolito Modular MVC + Capa de Servicios + Colas en Redis** *(Arquitectura Elegida)* | • Respuesta `< 2 s` ante 3,000 brigadistas gracias a Redis (`DA01`).<br>• Reglas de negocio aisladas y testeables (`DA06`).<br>• Despliegue simple en contenedores sin latencia de red entre módulos (`DA04`). | Cumple el 100% de las restricciones (`RC01`–`RC06`) y atributos de calidad (`AC01`–`AC06`) con la menor complejidad accidental. | ✅ **SELECCIONADA**<br>(`ADR-001`, `ADR-002`, `ADR-003`) |
| **B. MVC Tradicional sin Capa de Servicios** *(Lógica en Controllers o Models)* | • Menor cantidad inicial de archivos en proyectos pequeños. | Genera *Fat Controllers* acoplados a HTTP y a Eloquent, incumpliendo `DA06` (`AC06` - Mantenibilidad) y dificultando la deduplicación previa en Redis y el testeo unitario con mocks. | ❌ **DESCARTADA** |
| **C. Monolito con Escritura Síncrona Directa en MySQL** *(Sin Redis / Queues)* | • No requiere contenedor Redis ni procesos `queue:work`. | Ante un pico simultáneo de 3,000 brigadistas (`DA01`), las transacciones concurrentes de `INSERT` agotarían el pool de conexiones de MySQL (`max_connections`), causando errores HTTP `500/504` y pérdida de registros en campo. | ❌ **DESCARTADA** |
| **D. Arquitectura de Microservicios** *(Servicios separados para Auth, Captura, Padrón y Reportes)* | • Escalado independiente extremo por dominio en organizaciones grandes. | Introduce sobrecosto operativo (múltiples bases de datos, consistencia eventual entre servicios, API Gateway complejo) injustificado para un dominio cohesivo de 4 módulos que ya escala horizontalmente separando contenedores `Web` y `Workers`. | ❌ **DESCARTADA** |

---

## 5. Topología de Despliegue en Contenedores

Aunque el código fuente se mantiene en un solo repositorio monolítico Laravel (`ADR-001`), el despliegue en producción (`DA04`, `AC04`) separa responsabilidades en contenedores especializados para evitar puntos únicos de fallo:

* **Contenedor Balanceador (`Nginx`):** Recibe el tráfico HTTPS de los brigadistas y coordinadores y lo distribuye entre las réplicas web.
* **Contenedores Web (`xN` réplicas stateless de `App Web Laravel`):** Atienden las peticiones HTTP, validan tokens Sanctum y encolan los registros en Redis en `< 5 ms`.
* **Contenedor Broker y Caché (`Redis 7` con persistencia `AOF`):** Almacena la cola de trabajos sin pérdida ante reinicios, resuelve la deduplicación rápida de DNI (`SETNX`) y cachea los reportes por zona.
* **Contenedores Worker (`xM` procesos `php artisan queue:work`):** Consumen `RegistrarDamnificadoJob` desde Redis a ritmo controlado e insertan el padrón en MySQL sin saturar sus conexiones.
* **Contenedor / Servicio de Base de Datos (`MySQL 8 - padron_db`):** Resguarda de forma persistente e íntegra las tablas `users`, `zonas`, `damnificados` (`UNIQUE dni`) y `failed_jobs`.
