# Enfoque Arquitectónico: MVC en Capas con Servicios

## 1. Justificación del Enfoque

Para el **Sistema Transaccional de Empadronamiento**, se ha adoptado el enfoque de **MVC con una capa intermedia de servicios** dentro del framework Laravel. El patrón MVC tradicional de Laravel delegaría la lógica de negocio en los controllers; aquí se incorpora explícitamente una capa de *services* para mantener el corazón del sistema (reglas del padrón) independiente de HTTP.

| Elemento | Descripción aplicada al Sistema de Empadronamiento |
| :--- | :--- |
| **Patrón / Enfoque** | MVC (Model-View-Controller) con capa de servicios intermedia. |
| **Objetivo** que apunten siempre hacia las reglas de negocio (services y models), y nunca al revés. |
| **¿Qué problema resuelve?** | Evita el acoplamiento fuerte entre las reglas del padrón y los detalles técnicos (HTTP, Blade, MySQL, cola de mensajes). Si mañana la cola de Laravel cambia de driver `database` a `redis`, o el dashboard se consuma vía API, el negocio no se altera. |
| **Capas definidas** | Presentación (Blade), Control (Rutas, Middlewares, Controllers), Negocio (Services) y Datos (Models/Eloquent + MySQL). |
| **Beneficios** | Código testeable (los services se prueban sin HTTP), mantenible y modular, ideal para el escenario de alta concurrencia (3,000 usuarios). |

---

## 2. Diagrama de Capas (Módulo Empadronamiento)

```mermaid
flowchart LR
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef ctrl fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef biz fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef data fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef ext fill:#f9f9f9,stroke:#666,stroke-width:1px,color:#333

    Usuario["Actor\n(Brigadista / Coordinador)"]:::ext

    subgraph APP ["«aplicación» Laravel Empadronamiento [PHP 8 / Laravel]"]
        subgraph PRES ["PRESENTACIÓN\nresources/views/ (Blade + Bootstrap)"]
            direction TB
            V1["captura/create.blade.php\n(formulario mobile-first)"]:::pres
            V2["dashboard/index.blade.php\n(gráficos y consolidados)"]:::pres
        end

        subgraph CTRL ["CONTROL\nroutes/ + app/Http/"]
            direction TB
            MW["«middleware»\nAuthenticate (Sanctum)\nauth:sanctum"]:::ctrl
            C1["«controller»\nEmpadronamientoController\n(store, index)"]:::ctrl
        end

        subgraph BIZ ["NEGOCIO\napp/Services/"]
            direction TB
            S1["«service»\nEmpadronamientoService\nvalidar, duplicado, registrar"]:::biz
            S2["«job»\nRegistrarDamnificadoJob\nprocesa la cola"]:::biz
        end

        subgraph DATA ["DATOS\napp/Models/ + database/"]
            direction TB
            M1["«model»\nDamnificado\n(Eloquent)"]:::data
            M2["«model»\nZona\n(Eloquent)"]:::data
            MI["«migration»\nschema MySQL\n(clave única DNI)"]:::data
        end
    end

    BD["«sistema externo»\nMySQL\n(padron_db)"]:::ext

    Usuario -- "HTTP" --> PRES
    PRES --> MW
    MW --> C1
    C1 -- "invoca" --> S1
    S1 -. "encola" .-> S2
    S2 -- "usa" --> M1
    S1 --> M1
    M1 --> BD
    M2 --> BD
    MI -. "define" .-> BD

    PRES ~~~ CTRL
    CTRL ~~~ BIZ
    BIZ ~~~ DATA
```

---

## 3. Reglas de Dependencia

1. Las vistas (Blade) solo muestran datos que les envía un controller; no contienen reglas de negocio.
2. Los *controllers* solo reciben la petición, validan lo básico y delegan en un *service*: no acceden directamente a Eloquent ni a la cola.
3. Los *services* no conocen HTTP ni Blade: reciben arrays/objetos de dominio, aplican reglas y devuelven resultados (lo que permite reutilizarlos desde jobs, consola o API).
4. Los *models* (Eloquent) son la única puerta a MySQL, junto con las *migrations* que definen el esquema.
5. El registro masivo cruza la capa de negocio y la de datos mediante *jobs* de Laravel Queues, respetando la inversión de responsabilidades entre la recepción y la persistencia.
