# Arquitectura Inicial

## Diagrama de la Arquitectura Inicial

```mermaid
flowchart TD
    subgraph ACTORES ["ACTORES"]
        Brigadista["Brigadista"]
        Coordinador["Coordinador de Defensa Civil"]
    end

    subgraph PRESENTACION ["PRESENTACIÓN (Blade + Bootstrap + LocalStorage)"]
        Captura["Vista de Captura (Mobile-First)"]
        Dashboard["Vista de Explotación (Dashboard y Reportes)"]
    end

    subgraph NEGOCIO ["CONTROL Y LÓGICA DE NEGOCIO (Laravel - MVC con Servicios)"]
        Rutas["Rutas + Middlewares (Sanctum)"]
        Controllers["Controllers (Auth, Empadronamiento, Padrón, Reportes)"]
        Services["Services (Reglas de negocio y validación)"]
        Redis[("Broker de Colas y Caché (Redis)")]
        Jobs["Workers / Jobs de Registro (RegistrarDamnificadoJob)"]
    end

    subgraph DATOS ["DATOS (Eloquent ORM + MySQL)"]
        Models["Models (Damnificado, Zona, User)"]
        BD[("Base de Datos Relacional (MySQL - padron_db)")]
    end

    Brigadista --> Captura
    Coordinador --> Dashboard

    Captura -- "HTTPS (POST / GET)" --> Rutas
    Dashboard -- "HTTPS (GET)" --> Rutas
    Rutas --> Controllers
    Controllers -. "renderiza HTML" .-> Captura
    Controllers -. "renderiza HTML" .-> Dashboard
    Controllers --> Services
    Services -- "SETNX DNI + dispatch(DTO)" --> Redis
    Redis -- "consume job" --> Jobs
    Jobs -- "upsert()" --> Models
    Services -- "lectura padrón / login" --> Models
    Models --> BD

    style ACTORES fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style PRESENTACION fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style NEGOCIO fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style DATOS fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style Brigadista fill:#222,stroke:#fff,color:#fff
    style Coordinador fill:#222,stroke:#fff,color:#fff
    style Captura fill:#222,stroke:#fff,color:#fff
    style Dashboard fill:#222,stroke:#fff,color:#fff
    style Rutas fill:#222,stroke:#fff,color:#fff
    style Controllers fill:#222,stroke:#fff,color:#fff
    style Services fill:#222,stroke:#fff,color:#fff
    style Redis fill:#7a1f1f,stroke:#fff,color:#fff
    style Jobs fill:#222,stroke:#fff,color:#fff
    style Models fill:#222,stroke:#fff,color:#fff
    style BD fill:#1f5c1f,stroke:#fff,color:#fff
```

## Descripción de la Arquitectura Inicial

La solución se estructura como un **Monolito Modular en Laravel** con renderizado en servidor (**Blade**), organizado en capas y respaldado por **Redis** y **MySQL** para absorber picos de alta concurrencia:

* **Presentación (Blade + Bootstrap + JS LocalStorage):** Ofrece la *Vista de Captura* (*Mobile-First* con persistencia temporal en el navegador ante microcortes de señal) para los brigadistas en campo y el *Dashboard* de explotación para los coordinadores de Defensa Civil.
* **Control y Lógica de Negocio (Laravel + Redis):** Las rutas y middlewares validan el token de **Laravel Sanctum**; los *controllers* coordinan la petición HTTP y delegan en los *services*. En el registro masivo, `EmpadronamientoService` valida el DNI y verifica duplicados atómicamente en **Redis** (`SETNX`), despachando un DTO ligero a la cola en memoria para que los *Workers* (`RegistrarDamnificadoJob`) procesen hasta 3,000 registros concurrentes sin saturar el motor relacional.
* **Datos (Eloquent + MySQL):** Los *workers* persisten de forma controlada e idempotente (`upsert`) a través de los modelos **Eloquent** sobre **MySQL (`padron_db`)**, garantizando la normalización y la restricción física de unicidad del DNI (`UNIQUE`).
