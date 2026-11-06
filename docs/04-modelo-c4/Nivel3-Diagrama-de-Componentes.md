# Nivel 3 - Diagrama de Componentes

Vista interna del contenedor **App Web Laravel** y su relación con el contenedor **Workers de Cola**, **Redis** y **MySQL**, aplicando el patrón arquitectónico **MVC en Capas con Servicios** (`ADR-002`).

```mermaid
flowchart TD
    classDef client fill:#666666,stroke:#fff,stroke-width:2px,color:#fff
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef ctrl fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef biz fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef data fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef queue fill:#d9534f,stroke:#fff,stroke-width:2px,color:#fff
    classDef db fill:#2d882d,stroke:#fff,stroke-width:2px,color:#fff

    Nav["Cliente Web\n(Navegador Móvil / Escritorio)"]:::client

    subgraph WEB_CONTAINER ["Contenedor: App Web Laravel"]
        subgraph CTRL ["1. Capa de Control (routes/ + app/Http/)"]
            MW["Middleware auth:sanctum\n+ FormRequest Validation"]:::ctrl
            C_Auth["AuthController"]:::ctrl
            C_Cap["EmpadronamientoController"]:::ctrl
            C_Pad["PadronController"]:::ctrl
            C_Rep["ReporteController"]:::ctrl
        end

        subgraph PRES ["2. Capa de Presentación (resources/views/ - Blade + Bootstrap)"]
            V_Auth["auth/login.blade.php"]:::pres
            V_Cap["captura/create.blade.php\n(Formulario Mobile-First)"]:::pres
            V_Dash["dashboard/index.blade.php\n(Padrón Consolidado)"]:::pres
            V_Rep["reportes/*.blade.php\n(Estadísticas por Zona)"]:::pres
        end

        subgraph BIZ ["3. Capa de Negocio (app/Services/)"]
            S_Auth["AuthService\n(Emite/revoca tokens Sanctum)"]:::biz
            S_Cap["EmpadronamientoService\n(Valida DNI, deduplica en Redis y encola DTO)"]:::biz
            S_Pad["PadronService\n(Consulta padrón consolidado)"]:::biz
            S_Rep["ReporteService\n(Métricas por zona con caché)"]:::biz
        end

        subgraph DATA ["4. Capa de Datos (app/Models/)"]
            M_User["User (Eloquent)"]:::data
            M_Dam["Damnificado (Eloquent)"]:::data
            M_Zona["Zona (Eloquent)"]:::data
        end
    end

    subgraph WORKER_CONTAINER ["Contenedor: Workers de Cola (queue:work)"]
        J_Reg["RegistrarDamnificadoJob\n(app/Jobs/ - Consume DTO y persiste)"]:::biz
    end

    Redis[("Contenedor: Redis 7\n(Colas + Deduplicación DNI + Caché)")]:::queue
    DB[("Contenedor: MySQL 8\n(padron_db)")]:::db

    %% Flujo HTTP entrante y renderizado de Vistas
    Nav -- "HTTPS (GET / POST)" --> MW
    MW --> C_Auth
    MW --> C_Cap
    MW --> C_Pad
    MW --> C_Rep

    C_Auth -. "renderiza" .-> V_Auth
    C_Cap -. "renderiza" .-> V_Cap
    C_Pad -. "renderiza" .-> V_Dash
    C_Rep -. "renderiza" .-> V_Rep

    %% Delegación a Capa de Negocio
    C_Auth --> S_Auth
    C_Cap --> S_Cap
    C_Pad --> S_Pad
    C_Rep --> S_Rep

    %% Interacciones de Negocio con Redis y Datos
    S_Auth --> M_User
    S_Cap -- "SETNX dni + dispatch(DTO)" --> Redis
    Redis -- "BLPOP job" --> J_Reg
    J_Reg -- "Damnificado::upsert()" --> M_Dam

    S_Pad --> M_Dam
    S_Pad --> M_Zona
    S_Rep --> M_Zona
    S_Rep -- "Cache::remember()" --> Redis

    %% Persistencia Eloquent hacia MySQL
    M_User --> DB
    M_Dam --> DB
    M_Zona --> DB
```

## Reglas de Dependencia entre Componentes

| Componente | Depende de | Prohibido depender de | Responsabilidad Clave |
| :--- | :--- | :--- | :--- |
| **Middlewares (`auth:sanctum`)** | Tokens Sanctum / Caché Redis | Services de dominio, Models de negocio | Interceptar peticiones HTTP, validar autenticación y formato de entrada antes del Controller. |
| **Controllers (`app/Http/Controllers`)** | Abstracciones de Services (`*ServiceInterface`), Vistas Blade | Eloquent (`Models`), `Redis`, `Jobs` directamente | Recibir la petición HTTP, delegar la acción al Service del módulo y devolver la vista Blade renderizada o respuesta HTTP `202`. |
| **Vistas Blade (`resources/views`)** | Datos (DTOs/ViewModels) inyectados por el Controller | Services, Models Eloquent, MySQL | Renderizar HTML5 + Bootstrap en el servidor sin ejecutar consultas ni reglas de negocio. |
| **Services (`app/Services`)** | `Redis` (caché/colas), `Jobs`, `Models` (en lecturas/login) | `Request`/`Response` HTTP, Vistas Blade | Encapsular la lógica de negocio: `EmpadronamientoService` valida el DNI, verifica duplicados atómicamente en Redis y encola `RegistrarDamnificadoJob`. |
| **Jobs (`app/Jobs`)** | Modelo `Damnificado` (Eloquent) | HTTP, Vistas Blade, `EmpadronamientoService` (evita ciclos) | Ejecutar en el contenedor `Worker` la inserción idempotente (`upsert`) del DTO encolado hacia MySQL. |
| **Models (`app/Models`)** | Motor relacional `MySQL` (`padron_db`) | Controllers, Services, Vistas | Mapear las tablas `users`, `zonas` y `damnificados` y sus restricciones de integridad. |
