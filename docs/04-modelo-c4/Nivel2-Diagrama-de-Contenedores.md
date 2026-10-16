# Nivel 2 - Diagrama de Contenedores

Descomposición del sistema en unidades desplegables (contenedores): aplicación web Laravel, worker de la cola y base de datos.

```mermaid
flowchart TD
    classDef person fill:#08427b,stroke:#fff,stroke-width:2px,color:#fff
    classDef web fill:#1168bd,stroke:#fff,stroke-width:2px,color:#fff
    classDef worker fill:#438dd5,stroke:#fff,stroke-width:2px,color:#fff
    classDef db fill:#2d882d,stroke:#fff,stroke-width:2px,color:#fff
    classDef ext fill:#999999,stroke:#fff,stroke-width:2px,color:#fff

    Brigadista["Brigadista\n( actor humano )"]:::person
    Coordinador["Coordinador de Defensa Civil\n( actor humano )"]:::person

    subgraph SYSTEM ["Sistema de Empadronamiento"]
        App["App Web Laravel\n( contenedor web )\nRutas, Middlewares (Sanctum),\nControllers, Services,\nVistas Blade + Bootstrap"]:::web
        Worker["Worker de Cola\n( contenedor worker )\nphp artisan queue:work\nRegistrarDamnificadoJob"]:::worker
        DB[("MySQL\n( contenedor / servicio )\npadron_db\nusers, zonas, damnificados")]:::db
    end

    Navegador["Navegador móvil / escritorio\n( sistema externo del usuario )"]:::ext

    Brigadista --> Navegador
    Coordinador --> Navegador
    Navegador -- "HTTPS (Blade + Bootstrap)" --> App
    App -- "cola de trabajos\n( driver database / redis )" --> Worker
    Worker -- "SQL / TCP 3306" --> DB
    App -- "SQL / TCP 3306 (Eloquent)" --> DB
```

## Contenedores

| Contenedor | Tecnología | Responsabilidad |
|---|---|---|
| App Web Laravel | PHP 8 + Laravel (Blade, Bootstrap) | Atiende HTTP, autentica con Sanctum, aplica reglas vía services y renderiza vistas. |
| Worker de Cola | Laravel Queues (`queue:work`) | Procesa los jobs de registro masivo de forma asíncrona. |
| MySQL | MySQL 8 | Persistencia relacional del padrón transaccional. |

> Nota: la comunicación entre la app y el worker se realiza a través de la cola (tabla de jobs o Redis), nunca mediante llamadas directas al proceso worker.
