# Componentes Arquitectónicos

Descomposición del monolito Laravel en los componentes que conforman cada capa y su interacción con **Redis** y **MySQL** para atender el caso de uso crítico: *registrar a un damnificado en condiciones de alta concurrencia (3,000 brigadistas simultáneos)*.

## 1. Inventario de Componentes

| Componente | Carpeta / Ubicación | Responsabilidad |
| :--- | :--- | :--- |
| **Rutas** | `routes/web.php`, `routes/api.php` | Mapean URLs a controllers y declaran los middlewares aplicados. |
| **Middlewares y Requests** | `app/Http/Middleware/`, `app/Http/Requests/` | Validan el token de **Laravel Sanctum**, permisos de rol y formato de entrada (`StoreDamnificadoRequest`) antes de llegar al controller. |
| **Controllers** | `app/Http/Controllers/` | Reciben la petición HTTP, delegan en la interfaz del *service* y devuelven la vista Blade o confirmación HTTP `202 Accepted`. Sin reglas de negocio. |
| **Services** | `app/Services/` | Concentran las reglas de negocio: validación de DNI, deduplicación atómica en **Redis**, despacho de DTO a la cola, consolidación y métricas del padrón. |
| **Jobs** | `app/Jobs/` | Unidades de trabajo serializables (`RegistrarDamnificadoJob`) consumidas desde **Redis** por los procesos `queue:work` para persistir en MySQL. |
| **Models (Eloquent)** | `app/Models/` | Representan las entidades (`Damnificado`, `Zona`, `User`) y ejecutan operaciones `upsert` y consultas sobre **MySQL**. |
| **Migrations** | `database/migrations/` | Definen el esquema relacional: claves primarias, restricción física `UNIQUE (dni)` y claves foráneas a `zonas`. |
| **Vistas (Blade)** | `resources/views/` | Presentación renderizada en servidor con Bootstrap: formulario de captura *Mobile-First* (con respaldo en `localStorage`) y dashboard del coordinador. |

## 2. Diagrama de Componentes (Flujo de Registro de Damnificado)

```mermaid
flowchart TD
    classDef client fill:#666666,stroke:#fff,stroke-width:2px,color:#fff
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef ctrl fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef biz fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef data fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef queue fill:#fde8e8,stroke:#d9534f,stroke-width:1px,color:#333
    classDef ext fill:#e8f8e8,stroke:#2d882d,stroke-width:1px,color:#333

    Nav["«cliente web»\nNavegador Móvil del Brigadista\n(Bootstrap + JS LocalStorage)"]:::client

    subgraph LARAVEL ["«contenedor» App Web Laravel"]
        R["«ruta»\nPOST /captura"]:::ctrl
        MW["«middleware + request»\nauth:sanctum\n+ StoreDamnificadoRequest"]:::ctrl
        C["«controller»\nEmpadronamientoController::store"]:::ctrl
        V["«vista»\ncaptura/create.blade.php\n(Confirmación de recepción)"]:::pres
        S["«service»\nEmpadronamientoService\n(valida DNI, deduplica en Redis y encola DTO)"]:::biz
    end

    Redis[("«contenedor broker»\nRedis 7 (AOF)\nCola + SETNX dni")]:::queue

    subgraph WORKER ["«contenedor» Worker de Cola (queue:work)"]
        J["«job»\nRegistrarDamnificadoJob\n(handle: recibe array DTO)"]:::biz
        M["«model»\nDamnificado (Eloquent)\nupsertPadron()"]:::data
        MI["«migration»\nUNIQUE (dni)"]:::data
    end

    DB[("«contenedor BD»\nMySQL 8 (padron_db)")]:::ext

    Nav -- "1. POST /captura (Token Sanctum)" --> R
    R --> MW
    MW --> C
    C -- "2. registrar(datos)" --> S
    S -- "3. SETNX dni + dispatch(DTO)" --> Redis
    C -. "4. Respuesta inmediata (< 2 s)" .-> V
    V -. "HTML confirmación" .-> Nav

    Redis -- "5. BLPOP job (asíncrono)" --> J
    J -- "6. upsertPadron(datos)" --> M
    M --> MI
    M -- "7. SQL INSERT / UPSERT" --> DB
```

## 3. Flujo Resumido de Alta Concurrencia

1. **Envío desde campo:** El brigadista envía el formulario desde su navegador móvil; si hay microcorte de señal, el cliente conserva el registro en `localStorage` hasta confirmar recepción.
2. **Validación de entrada y autenticación:** El middleware `auth:sanctum` y `StoreDamnificadoRequest` validan el token y la estructura de los campos antes de invocar al controlador.
3. **Deduplicación en memoria y encolamiento (`< 5 ms`):** `EmpadronamientoController::store` delega en `EmpadronamientoService`, el cual comprueba atómicamente el DNI en **Redis** (`SETNX dni:{numero}`) y despacha `RegistrarDamnificadoJob` con el arreglo de datos (`DTO`) hacia la cola en Redis, **sin abrir conexiones de escritura contra MySQL**.
4. **Respuesta inmediata al brigadista:** El controlador devuelve de inmediato la vista de confirmación (`< 2 s`), liberando al brigadista para continuar empadronando.
5. **Persistencia asíncrona controlada:** Los contenedores *Worker* (`php artisan queue:work`) consumen los jobs desde Redis y ejecutan `Damnificado::upsertPadron()` sobre **MySQL**, donde el índice `UNIQUE (dni)` actúa como garantía física definitiva de integridad del padrón.
