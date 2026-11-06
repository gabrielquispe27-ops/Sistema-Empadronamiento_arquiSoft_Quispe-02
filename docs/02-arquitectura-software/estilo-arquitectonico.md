# Patrón Arquitectónico

## 1. Definición del Patrón Arquitectónico Global

Para el **Sistema Transaccional de Respuesta Rápida para el Empadronamiento**, se ha seleccionado e implementado el patrón arquitectónico **Modelo-Vista-Controlador (MVC) con Capa de Servicios** dentro de un **Monolito Modular**, operando bajo un esquema **Cliente-Servidor** con renderizado en servidor (SSR) y **procesamiento asíncrono desacoplado mediante Redis**.

* **Cliente-Servidor con Balanceo:** Separa el cliente web (vistas HTML5/Bootstrap 5 con respaldo en `localStorage` para microcortes en campo) del clúster de contenedores de servidor presidido por **Nginx**.
* **Monolito Modular:** Todas las funcionalidades residen en un mismo repositorio y base de código Laravel, particionado internamente en cuatro módulos de negocio cohesivos: **1. Autenticación (`Auth`)**, **2. Captura/Empadronamiento**, **3. Padrón** y **4. Reportes**.
* **Separación Estricta MVC (`ADR-002`, `ADR-003`):**
  * **Componente Controlador (`routes/`, `app/Http/`):** Enrutamiento HTTP (`web.php`, `api.php`), middlewares transversales (`auth:sanctum`, `EnsureRoleMiddleware`), validación de entrada (`LoginRequest`, `StoreDamnificadoRequest`) y controladores delgados (`AuthController`, `EmpadronamientoController`, `PadronController`, `ReporteController`).
  * **Componente Vista (`resources/views/`):** Plantilla maestra (`layouts/app.blade.php`) y vistas Blade por módulo (`auth/login.blade.php`, `captura/create.blade.php`, `dashboard/index.blade.php`, `reportes/index.blade.php`).
  * **Componente Modelo (`app/Services/`, `app/Jobs/`, `app/Models/`, `database/migrations/`):** Subcapa de lógica de negocio e inversión de dependencias (`EmpadronamientoServiceInterface`, `AuthService`, `EmpadronamientoService`, `PadronService`, `ReporteService`), procesamiento asíncrono en cola (`RegistrarDamnificadoJob`) y persistencia Eloquent (`User`, `Damnificado`, `Zona`) sobre **MySQL 8** y **Redis 7**.

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

## 3. Topología de Despliegue en Contenedores

Aunque el código fuente se mantiene en un solo repositorio monolítico Laravel (`ADR-001`), el despliegue en producción (`DA04`, `AC04`) separa responsabilidades en contenedores especializados para evitar puntos únicos de fallo:

* **Contenedor Balanceador (`Nginx`):** Recibe el tráfico HTTPS de los brigadistas y coordinadores y lo distribuye entre las réplicas web.
* **Contenedores Web (`xN` réplicas stateless de `App Web Laravel`):** Atienden las peticiones HTTP, validan tokens Sanctum y encolan los registros en Redis en `< 5 ms`.
* **Contenedor Broker y Caché (`Redis 7` con persistencia `AOF`):** Almacena la cola de trabajos sin pérdida ante reinicios, resuelve la deduplicación rápida de DNI (`SETNX`) y cachea los reportes por zona.
* **Contenedores Worker (`xM` procesos `php artisan queue:work`):** Consumen `RegistrarDamnificadoJob` desde Redis a ritmo controlado e insertan el padrón en MySQL sin saturar sus conexiones.
* **Contenedor / Servicio de Base de Datos (`MySQL 8 - padron_db`):** Resguarda de forma persistente e íntegra las tablas `users`, `zonas`, `damnificados` (`UNIQUE dni`) y `failed_jobs`.
