# Nivel 2 - Diagrama de Contenedores

Descomposición del **Sistema de Empadronamiento en Zonas de Emergencia** en unidades desplegables y de ejecución independientes (contenedores), diseñada para absorber picos de **3,000 brigadistas concurrentes** sin puntos únicos de fallo en la capa web ni bloqueos en la base de datos relacional.

```mermaid
flowchart TD
    classDef person fill:#08427b,stroke:#fff,stroke-width:2px,color:#fff
    classDef proxy fill:#0b4d8c,stroke:#fff,stroke-width:2px,color:#fff
    classDef web fill:#1168bd,stroke:#fff,stroke-width:2px,color:#fff
    classDef queue fill:#d9534f,stroke:#fff,stroke-width:2px,color:#fff
    classDef worker fill:#438dd5,stroke:#fff,stroke-width:2px,color:#fff
    classDef db fill:#2d882d,stroke:#fff,stroke-width:2px,color:#fff
    classDef client fill:#666666,stroke:#fff,stroke-width:2px,color:#fff

    Brigadista["Brigadista\n[Persona]\nRegistra damnificados en campo"]:::person
    Coordinador["Coordinador de Defensa Civil\n[Persona]\nAnaliza padrón y cobertura"]:::person

    Navegador["Cliente Web (Móvil / Escritorio)\n[Navegador Web: HTML5, Bootstrap, JS LocalStorage]\nRenderiza vistas Blade y gestiona reintentos locales"]:::client

    subgraph SYSTEM ["Sistema de Empadronamiento en Zonas de Emergencia"]
        LB["Balanceador / Reverse Proxy\n[Contenedor: Nginx]\nDistribuye tráfico HTTPS hacia réplicas web"]:::proxy

        App["App Web Laravel (xN réplicas Stateless)\n[Contenedor: PHP 8.x + Laravel / Octane]\nAutentica con Sanctum, valida reglas en Services\ny renderiza vistas Blade"]:::web

        Redis[("Broker de Colas y Caché\n[Contenedor: Redis 7 con AOF]\nColas de Laravel Queues, deduplicación\nrápida de DNI y caché de dashboard")]:::queue

        Worker["Workers de Cola (xM procesos)\n[Contenedor: PHP 8.x + queue:work / Supervisor]\nEjecuta RegistrarDamnificadoJob en segundo plano"]:::worker

        DB[("Base de Datos del Padrón\n[Contenedor / Servicio: MySQL 8 - padron_db]\nAlmacena users, zonas, damnificados (UK dni)\ny failed_jobs")]:::db
    end

    Brigadista -- "Captura datos demográficos\ny daños materiales" --> Navegador
    Coordinador -- "Consulta dashboard\ny reportes por zona" --> Navegador

    Navegador -- "HTTPS (Formularios / Token Sanctum)" --> LB
    LB -- "HTTP interno" --> App

    App -- "1. Valida token/DNI en caché y\nencola DTO (TCP 6379)" --> Redis
    Worker -- "2. Consume jobs de la cola\n(BLPOP TCP 6379)" --> Redis
    Worker -- "3. Persiste padrón con Eloquent\n(Upsert SQL / TCP 3306)" --> DB
    App -- "Lectura de padrón consolidado y login\n(SQL / TCP 3306)" --> DB
```

## Inventario de Contenedores

| Contenedor | Tecnología | Responsabilidad Arquitectónica |
| :--- | :--- | :--- |
| **Cliente Web (Móvil / Escritorio)** | HTML5, Bootstrap (Mobile-First), JS (`localStorage`) | Ejecuta la interfaz en el navegador del actor; retiene temporalmente el formulario en almacenamiento local ante microcortes de red en campo (`RC07`). |
| **Balanceador / Reverse Proxy** | Nginx (Contenedor) | Termina conexiones HTTPS y reparte la carga entre las $N$ réplicas stateless de la aplicación web para evitar un punto único de fallo (`AC04`). |
| **App Web Laravel (`xN` réplicas)** | PHP 8.x + Laravel / Octane | Atiende peticiones HTTP, valida tokens de **Laravel Sanctum**, verifica duplicados en memoria y despacha DTOs a la cola sin bloquear MySQL (`DA01`, `ADR-002`). |
| **Broker de Colas y Caché** | Redis 7 (con persistencia `AOF`) | Actúa como intermediario del patrón *Productor-Consumidor* para **Laravel Queues**, realiza deduplicación atómica de DNI (`SETNX`) en `< 1 ms` y cachea tokens y métricas del dashboard (`ADR-003`). |
| **Workers de Cola (`xM` procesos)** | PHP 8.x + `php artisan queue:work` (Supervisor) | Consume asíncronamente `RegistrarDamnificadoJob` desde Redis y ejecuta la persistencia transaccional controlada hacia MySQL. |
| **Base de Datos del Padrón** | MySQL 8 (`padron_db`) | Fuente de verdad relacional del padrón consolidado (`users`, `zonas`, `damnificados` con restricción física `UNIQUE (dni)` y `failed_jobs`) (`DA03`, `AC03`). |

> [!IMPORTANT]
> **Desacoplamiento Productor-Consumidor:** La `App Web Laravel` nunca invoca directamente al `Worker de Cola` ni ejecuta escrituras pesadas en `MySQL` durante el `POST /captura`. Toda la comunicación asíncrona ocurre a través del contenedor **Redis**, garantizando tiempos de respuesta `< 2 s` bajo alta concurrencia.
