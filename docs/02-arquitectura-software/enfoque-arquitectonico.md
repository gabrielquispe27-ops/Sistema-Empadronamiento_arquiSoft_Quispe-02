# Enfoque Arquitectónico: MVC en Capas con Servicios

## 1. Justificación del Enfoque

Para el **Sistema Transaccional de Empadronamiento**, se ha adoptado el enfoque de **MVC con una capa intermedia de servicios** dentro del framework Laravel, complementado con **procesamiento asíncrono sobre Redis**. Mientras que el patrón MVC tradicional suele acoplar las reglas de negocio a los controladores o modelos, la capa explícita de *services* mantiene las reglas del padrón independientes del transporte HTTP.

| Elemento | Descripción aplicada al Sistema de Empadronamiento |
| :--- | :--- |
| **Patrón / Enfoque** | MVC (Model-View-Controller) con capa de servicios intermedia y cola asíncrona (*Productor-Consumidor*). |
| **Objetivo** | Lograr que las dependencias apunten siempre hacia las reglas de negocio (*services* y contratos), desacoplando la recepción HTTP de la escritura en base de datos. |
| **¿Qué problema resuelve?** | Evita el acoplamiento entre las reglas del padrón y los detalles técnicos (HTTP, Blade, MySQL, Redis) y elimina el bloqueo de MySQL durante ráfagas de 3,000 brigadistas concurrentes (`DA01`, `DA06`). |
| **Capas definidas** | Presentación (Blade + Bootstrap), Control (Rutas, Middlewares, Controllers), Negocio (Services + Jobs) y Datos (Models/Eloquent + MySQL), apoyadas por Redis como broker de colas y caché. |
| **Beneficios** | Código testeable mediante interfaces (principio DIP), respuestas HTTP `< 2 s` al encolar en memoria y cero duplicados mediante validación en Redis + restricción `UNIQUE` en MySQL. |

---

## 2. Diagrama de Capas (Módulo Empadronamiento)

```mermaid
flowchart LR
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef ctrl fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef biz fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef data fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef queue fill:#fde8e8,stroke:#d9534f,stroke-width:1px,color:#333
    classDef ext fill:#f9f9f9,stroke:#666,stroke-width:1px,color:#333

    Usuario["Actor\n(Brigadista / Coordinador)"]:::ext

    subgraph APP ["«aplicación» Laravel Empadronamiento [PHP 8 / Laravel]"]
        subgraph CTRL ["1. CONTROL\nroutes/ + app/Http/"]
            direction TB
            MW["«middleware»\nAuthenticate (Sanctum)\nauth:sanctum"]:::ctrl
            C1["«controller»\nEmpadronamientoController\n(create, store)"]:::ctrl
        end

        subgraph PRES ["2. PRESENTACIÓN\nresources/views/ (Blade + Bootstrap)"]
            direction TB
            V1["captura/create.blade.php\n(formulario mobile-first)"]:::pres
            V2["dashboard/index.blade.php\n(gráficos y consolidados)"]:::pres
        end

        subgraph BIZ ["3. NEGOCIO\napp/Services/ + app/Jobs/"]
            direction TB
            S1["«service»\nEmpadronamientoService\n(valida DNI, deduplica en Redis, encola DTO)"]:::biz
            S2["«job / worker»\nRegistrarDamnificadoJob\n(consume DTO y ejecuta upsert)"]:::biz
        end

        subgraph DATA ["4. DATOS\napp/Models/ + database/"]
            direction TB
            M1["«model»\nDamnificado\n(Eloquent)"]:::data
            M2["«model»\nZona\n(Eloquent)"]:::data
            MI["«migration»\nschema MySQL\n(clave única DNI)"]:::data
        end
    end

    Redis[("«contenedor broker»\nRedis 7\n(Colas + Caché DNI)")]:::queue
    BD[("«contenedor BD»\nMySQL 8\n(padron_db)")]:::ext

    Usuario -- "HTTPS (GET/POST)" --> MW
    MW --> C1
    C1 -. "renderiza HTML" .-> V1
    C1 -. "renderiza HTML" .-> V2
    C1 -- "invoca (DIP)" --> S1
    S1 -- "SETNX dni + dispatch(DTO)" --> Redis
    Redis -- "BLPOP" --> S2
    S2 -- "upsertPadron()" --> M1
    M1 --> BD
    M2 --> BD
    MI -. "define esquema" .-> BD
```

---

## 3. Reglas de Dependencia

1. **Flujo HTTP y Presentación:** Las peticiones HTTP entran siempre por la capa de **Control** (`Rutas` → `Middleware auth:sanctum` → `Controllers`). Los controladores invocan a los *services* y finalmente renderizan las vistas **Blade**; las vistas jamás contienen lógica de negocio ni consultas SQL.
2. **Controladores Delgados:** Los *controllers* validan el formato de la petición (`FormRequest`) y delegan en la interfaz del *service* (`EmpadronamientoServiceInterface`): no acceden directamente a Eloquent, Redis ni la cola.
3. **Servicios Independientes de HTTP:** Los *services* reciben estructuras de datos tipadas/arreglos, validan las reglas de dominio, reservan el DNI atómicamente en **Redis** y despachan el trabajo a la cola sin bloquear MySQL.
4. **Flujo Unidireccional en Jobs:** `RegistrarDamnificadoJob` recibe un DTO serializable (`array $datos`) desde Redis y persiste mediante el modelo Eloquent `Damnificado` (`upsert`), sin volver a llamar a `EmpadronamientoService`.
5. **Integridad en Capa de Datos:** Los *models* (Eloquent) y las *migrations* son la única puerta hacia **MySQL**, resguardando la clave única `UNIQUE (dni)` como garantía física final.
