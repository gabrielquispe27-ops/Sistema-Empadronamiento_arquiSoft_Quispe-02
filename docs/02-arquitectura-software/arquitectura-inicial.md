# Arquitectura Inicial

## Diagrama de la Arquitectura Inicial

```mermaid
flowchart TD
    subgraph ACTORES ["ACTORES"]
        Brigadista["Brigadista"]
        Coordinador["Coordinador de Defensa Civil"]
    end

    subgraph PRESENTACION ["PRESENTACIÓN (Blade + Bootstrap)"]
        Captura["Vista de Captura (Mobile-First)"]
        Dashboard["Vista de Explotación (Dashboard)"]
    end

    subgraph NEGOCIO ["LÓGICA DE NEGOCIO (Laravel - MVC con servicios)"]
        Auth["Autenticación (Sanctum)"]
        Rutas["Rutas + Middlewares"]
        Controllers["Controllers"]
        Services["Services (reglas de negocio)"]
        Jobs["Jobs de Registro (Encolamiento)"]
    end

    subgraph DATOS ["DATOS (Eloquent ORM)"]
        Models["Models (Damnificado, Zona, Usuario)"]
        BD[("Base de Datos Relacional (MySQL - Padrón)")]
    end

    Brigadista --> Captura
    Coordinador --> Dashboard

    Captura --> Rutas
    Dashboard --> Rutas
    Rutas --> Auth
    Rutas --> Controllers
    Controllers --> Services
    Services --> Jobs
    Jobs --> Models
    Services --> Models
    Models --> BD

    style ACTORES fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style PRESENTACION fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style NEGOCIO fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style DATOS fill:#222,stroke:#fff,stroke-width:2px,color:#fff
    style Brigadista fill:#222,stroke:#fff,color:#fff
    style Coordinador fill:#222,stroke:#fff,color:#fff
    style Captura fill:#222,stroke:#fff,color:#fff
    style Dashboard fill:#222,stroke:#fff,color:#fff
    style Auth fill:#222,stroke:#fff,color:#fff
    style Rutas fill:#222,stroke:#fff,color:#fff
    style Controllers fill:#222,stroke:#fff,color:#fff
    style Services fill:#222,stroke:#fff,color:#fff
    style Jobs fill:#222,stroke:#fff,color:#fff
    style Models fill:#222,stroke:#fff,color:#fff
    style BD fill:#222,stroke:#fff,color:#fff
```

## Descripción de la Arquitectura Inicial

La solución es un **monolito Laravel** con presentación en servidor (Blade), separado en capas y con procesamiento asíncrono para soportar alta demanda:

* **Presentación (Blade + Bootstrap):** Ofrece la *Vista de Captura* (Mobile-First para brigadistas en campo) y el *Dashboard* (explotación de datos para coordinadores), renderizadas por el servidor.
* **Lógica de Negocio (Laravel):** Rutas y middlewares validan el token de *Sanctum*, los *controllers* coordinan la petición y los *services* concentran las reglas de validación y consolidación del padrón. El registro masivo pasa por *Jobs de Registro (Laravel Queues)* para procesar picos de hasta 3,000 usuarios sin saturar el sistema.
* **Datos (Eloquent + MySQL):** Garantiza la persistencia, normalización e integridad del padrón único de damnificados mediante modelos Eloquent sobre MySQL.
