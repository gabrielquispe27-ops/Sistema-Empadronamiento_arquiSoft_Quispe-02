# Estilo Arquitectónico

## 1. Definición del Estilo Global

Para el **Sistema Transaccional de Respuesta Rápida para el Empadronamiento**, se ha seleccionado un estilo arquitectónico de **Monolito Modular organizado en Capas (MVC con Capa de Servicios)**, operando bajo un modelo **Cliente-Servidor** con renderizado en servidor y **procesamiento asíncrono desacoplado mediante Redis**.

* **Cliente-Servidor con Balanceo:** Separa el cliente web (vistas HTML5/Bootstrap con respaldo en `localStorage` para microcortes en campo) del clúster de contenedores de servidor presidido por **Nginx**.
* **Monolito Modular:** Todas las funcionalidades residen en un mismo repositorio y base de código Laravel, particionado internamente en cuatro módulos de negocio cohesivos: **Autenticación (`Auth`)**, **Captura/Empadronamiento**, **Padrón** y **Reportes**.
* **Arquitectura en Capas (MVC + Services + Queues):** Las responsabilidades se dividen estrictamente en **Control** (Rutas, Middlewares, Controllers), **Presentación** (Vistas Blade renderizadas por los Controllers), **Lógica de Negocio** (Services y Jobs) y **Datos** (Models Eloquent + Migrations sobre MySQL), utilizando **Redis** como broker en memoria para absorber picos de 3,000 brigadistas concurrentes.

## 2. Diagrama de la Arquitectura (Vista Estructural)

```mermaid
flowchart TD
    %% ESTILOS
    classDef actor fill:#f9f9f9,stroke:#333,stroke-width:1px;
    classDef frontend fill:#fff,stroke:#333,stroke-width:1px;
    classDef capa1 fill:#e6f2ff,stroke:#66a3ff,stroke-width:1px;
    classDef capa2 fill:#e6ffe6,stroke:#66cc66,stroke-width:1px;
    classDef capa3 fill:#fff2e6,stroke:#ff9933,stroke-width:1px;
    classDef infra fill:#f2e6ff,stroke:#9966cc,stroke-width:1px;
    classDef queue fill:#fde8e8,stroke:#d9534f,stroke-width:1px;
    classDef db fill:#e8f8e8,stroke:#2d882d,stroke-width:1px;

    %% ACTORES Y FRONTEND
    Brigadista["Brigadista"]:::actor
    Coordinador["Coordinador de Defensa Civil"]:::actor

    subgraph FRONTEND ["Cliente Web (Navegador móvil / escritorio)"]
        UI["HTML5 / CSS / JS (LocalStorage)\n[Bootstrap Mobile-First + Dashboard]"]:::frontend
    end

    Brigadista --> UI
    Coordinador --> UI

    %% BACKEND MONOLITO
    subgraph BACKEND ["«monolito modular» Laravel Empadronamiento [PHP 8.x - Laravel]"]
        direction TB

        MW["Rutas + Middlewares (transversales)\nauth:sanctum - FormRequest - manejo de errores"]:::infra

        %% CAPA 1 - CONTROL
        subgraph CONTROL ["1. CAPA DE CONTROL (Recibe peticiones HTTP, delega en Services y renderiza Vistas)"]
            direction LR
            C_Auth["AuthController\n(login, logout, token)"]:::capa2
            C_Cap["EmpadronamientoController"]:::capa2
            C_Pad["PadronController"]:::capa2
            C_Rep["ReporteController"]:::capa2
        end

        %% CAPA 2 - PRESENTACIÓN
        subgraph PRESENTACION ["2. CAPA DE PRESENTACIÓN (Vistas Blade renderizadas en servidor)"]
            direction LR
            V_Auth["módulo auth\n(auth/login.blade.php)"]:::capa1
            V_Cap["módulo captura\n(captura/create.blade.php)"]:::capa1
            V_Dash["módulo dashboard\n(dashboard/index.blade.php)"]:::capa1
            V_Rep["módulo reportes\n(reportes/*.blade.php)"]:::capa1
        end

        %% CAPA 3 - NEGOCIO
        subgraph NEGOCIO ["3. CAPA DE LÓGICA DE NEGOCIO (Reglas de dominio y coordinación)"]
            direction LR
            S_Auth["AuthService\n(valida credenciales, emite token Sanctum)"]:::capa3
            S_Cap["EmpadronamientoService\n(valida DNI, deduplica en Redis y encola DTO)"]:::capa3
            S_Pad["PadronService\n(consolidación del padrón)"]:::capa3
            S_Rep["ReporteService\n(métricas por zona con caché)"]:::capa3
        end

        %% COLA
        subgraph COLA ["PROCESAMIENTO ASÍNCRONO (Contenedor Worker - queue:work)"]
            direction LR
            J_Cap["RegistrarDamnificadoJob\n(consume DTO y ejecuta upsert)"]:::infra
        end

        %% CAPA 4 - DATOS
        subgraph DATOS ["4. CAPA DE DATOS (Persistencia Relacional con Eloquent)"]
            direction LR
            M_User["User\n(Eloquent)"]:::capa1
            M_Dam["Damnificado\n(Eloquent)"]:::capa1
            M_Zona["Zona\n(Eloquent)"]:::capa1
            MI["migrations\n(clave única DNI, claves foráneas)"]:::capa1
        end

        %% CONEXIONES INTERNAS
        UI -- "HTTPS" --> MW
        MW --> C_Auth
        MW --> C_Cap
        MW --> C_Pad
        MW --> C_Rep

        C_Auth -. "renderiza" .-> V_Auth
        C_Cap -. "renderiza" .-> V_Cap
        C_Pad -. "renderiza" .-> V_Dash
        C_Rep -. "renderiza" .-> V_Rep

        C_Auth --> S_Auth
        C_Cap --> S_Cap
        C_Pad --> S_Pad
        C_Rep --> S_Rep

        J_Cap --> M_Dam
        S_Pad --> M_Dam
        S_Pad --> M_Zona
        S_Rep --> M_Zona
        S_Auth --> M_User
        M_Dam --> MI
    end

    %% INFRAESTRUCTURA DE DATOS Y COLAS
    REDIS[("Redis 7 (AOF)\n(Colas + Deduplicación DNI + Caché)")]:::queue
    DB[("MySQL 8\n(padron_db)")]:::db

    %% CONEXIONES CON REDIS Y MYSQL
    S_Cap -- "SETNX dni + dispatch(DTO)\n(TCP 6379)" --> REDIS
    REDIS -- "BLPOP job\n(TCP 6379)" --> J_Cap
    S_Rep -- "Cache::remember()\n(TCP 6379)" --> REDIS

    M_Dam -- "SQL - TCP 3306" --> DB
    M_Zona -- "SQL - TCP 3306" --> DB
    M_User -- "SQL - TCP 3306" --> DB

    %% REGLAS
    subgraph LEYENDA ["Reglas de la arquitectura"]
        R1["1. Las peticiones HTTP entran por Rutas/Middlewares hacia los Controllers; estos invocan Services y renderizan Vistas Blade.
2. En la ruta crítica de captura (POST /captura), EmpadronamientoService valida y encola en Redis sin bloquear MySQL.
3. RegistrarDamnificadoJob es procesado por los Workers y persiste de forma unidireccional en MySQL vía Eloquent (upsert).
4. Toda la lógica de negocio vive en Services, jamás en Controllers ni en Vistas Blade."]
    end
```

## 3. Topología de Despliegue en Contenedores

Aunque el código fuente se mantiene en un solo repositorio monolítico Laravel (`ADR-001`), el despliegue en producción (`DA04`, `AC04`) separa responsabilidades en contenedores especializados para evitar puntos únicos de fallo:

* **Contenedor Balanceador (`Nginx`):** Recibe el tráfico HTTPS de los brigadistas y coordinadores y lo distribuye entre las réplicas web.
* **Contenedores Web (`xN` réplicas stateless de `App Web Laravel`):** Atienden las peticiones HTTP, validan tokens Sanctum y encolan los registros en Redis en `< 5 ms`.
* **Contenedor Broker y Caché (`Redis 7` con persistencia `AOF`):** Almacena la cola de trabajos sin pérdida ante reinicios, resuelve la deduplicación rápida de DNI (`SETNX`) y cachea los reportes por zona.
* **Contenedores Worker (`xM` procesos `php artisan queue:work`):** Consumen `RegistrarDamnificadoJob` desde Redis a ritmo controlado e insertan el padrón en MySQL sin saturar sus conexiones.
* **Contenedor / Servicio de Base de Datos (`MySQL 8 - padron_db`):** Resguarda de forma persistente e íntegra las tablas `users`, `zonas`, `damnificados` (`UNIQUE dni`) y `failed_jobs`.
