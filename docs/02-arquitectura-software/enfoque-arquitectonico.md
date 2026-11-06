# Enfoque Arquitectónico: MVC en Capas con Servicios

## 1. Justificación del Enfoque

Para el **Sistema Transaccional de Empadronamiento**, se ha adoptado el enfoque de **MVC (Modelo-Vista-Controlador) con una capa intermedia de servicios** dentro del framework Laravel, complementado con **procesamiento asíncrono sobre Redis**. Mientras que el patrón MVC tradicional suele acoplar las reglas de negocio a los controladores o a los modelos ORM, la capa explícita de *services* e interfaces mantiene las reglas del padrón independientes del transporte HTTP.

| Elemento | Descripción aplicada al Sistema de Empadronamiento |
| :--- | :--- |
| **Patrón / Enfoque** | MVC (Modelo-Vista-Controlador) con capa de servicios intermedia y cola asíncrona (*Productor-Consumidor*). |
| **Objetivo** | Lograr que las dependencias apunten siempre hacia las abstracciones de negocio (`EmpadronamientoServiceInterface`), desacoplando la recepción HTTP de la escritura en base de datos. |
| **¿Qué problema resuelve?** | Evita el acoplamiento entre las reglas del padrón y los detalles técnicos (HTTP, Blade, MySQL, Redis) y elimina el bloqueo de MySQL durante ráfagas de 3,000 brigadistas concurrentes (`DA01`, `DA06`). |
| **Capas MVC definidas** | **1. Control (Componente Controlador):** Rutas, Middlewares (`auth:sanctum`, `EnsureRoleMiddleware`), `StoreDamnificadoRequest` y `EmpadronamientoController`.<br>**2. Presentación (Componente Vista):** Plantillas Blade (`captura/create.blade.php` y `layouts/app.blade.php`) con Bootstrap 5 y `localStorage`.<br>**3. Lógica de Negocio (Componente Modelo):** `EmpadronamientoServiceInterface`, `EmpadronamientoService` y `RegistrarDamnificadoJob`.<br>**4. Datos (Componente Modelo):** Modelos Eloquent (`Damnificado`, `Zona`, `User`) y migraciones sobre MySQL. |
| **Beneficios** | Código testeable mediante inversión de dependencias (DIP), respuestas HTTP `< 2 s` al encolar en memoria (`Redis`) y cero duplicados mediante validación atómica `SETNX` + restricción física `UNIQUE (dni)` en MySQL. |

---

## 2. Diagrama de Capas (Módulo Empadronamiento)

```mermaid
flowchart TD
    %% ESTILOS VISUALES DEL PROYECTO
    classDef actor fill:#f9f9f9,stroke:#333,stroke-width:1.5px,color:#111;
    classDef ctrl fill:#e6ffe6,stroke:#2ca02c,stroke-width:1.5px,color:#111;
    classDef mw fill:#f2e6ff,stroke:#9966cc,stroke-width:1.5px,color:#111;
    classDef view fill:#e8f4f8,stroke:#007acc,stroke-width:1.5px,color:#111;
    classDef contract fill:#fff9e6,stroke:#d4a017,stroke-width:1.5px,stroke-dasharray: 5 5,color:#111;
    classDef biz fill:#fff2e6,stroke:#ff9933,stroke-width:1.5px,color:#111;
    classDef job fill:#fce4ec,stroke:#c2185b,stroke-width:1.5px,color:#111;
    classDef data fill:#e8eaf6,stroke:#3f51b5,stroke-width:1.5px,color:#111;
    classDef queue fill:#fde8e8,stroke:#d9534f,stroke-width:1.5px,color:#111;
    classDef db fill:#e8f8e8,stroke:#2d882d,stroke-width:1.5px,color:#111;

    Brigadista["«actor»\nBrigadista en Campo\n(Dispositivo Móvil)"]:::actor

    subgraph APP ["«módulo captura / empadronamiento» Monolito Laravel [PHP 8.x — Patrón MVC en Capas con Servicios]"]
        direction TB

        %% ==========================================
        %% 1. CAPA DE CONTROL (COMPONENTE CONTROLADOR)
        %% ==========================================
        subgraph CTRL ["1. CAPA DE CONTROL (COMPONENTE CONTROLADOR — routes/ y app/Http/)"]
            direction LR
            R1["«rutas»\nroutes/web.php | routes/api.php\nGET /captura | POST /captura\nPOST /api/v1/empadronamiento"]:::mw
            MW1["«middlewares»\nauth:sanctum\nEnsureRoleMiddleware (role:brigadista)"]:::mw
            REQ1["«form request»\nStoreDamnificadoRequest\nValida DNI (8 dígitos), zona_id,\nnivel_dano y datos demográficos"]:::mw
            C1["«controller»\nEmpadronamientoController\n+ create(): View\n+ store(StoreDamnificadoRequest): RedirectResponse"]:::ctrl

            R1 --> MW1 --> REQ1 --> C1
        end

        %% ==========================================
        %% 2. CAPA DE PRESENTACIÓN (COMPONENTE VISTA)
        %% ==========================================
        subgraph PRES ["2. CAPA DE PRESENTACIÓN (COMPONENTE VISTA — resources/views/)"]
            direction LR
            V1["«vista blade»\ncaptura/create.blade.php\nFormulario Mobile-First (Bootstrap 5)\n+ Respaldo offline en localStorage"]:::view
            VL["«layout maestro»\nlayouts/app.blade.php\nEstructura HTML5 + Alertas de estado"]:::view

            V1 -. "extiende (@extends)" .-> VL
        end

        %% ==========================================
        %% 3. CAPA DE LÓGICA DE NEGOCIO (COMPONENTE MODELO - SERVICIOS Y COLA)
        %% ==========================================
        subgraph BIZ ["3. CAPA DE LÓGICA DE NEGOCIO (COMPONENTE MODELO — app/Services/ y app/Jobs/)"]
            direction LR
            I1["«interface (DIP)»\nEmpadronamientoServiceInterface\n+ registrar(array $datos): bool\n+ esDuplicado(string $dni): bool"]:::contract
            S1["«service (productor)»\nEmpadronamientoService\n• validarFormatoDni()\n• Redis::set(dni, NX, EX 86400)\n• dispatch(RegistrarDamnificadoJob)"]:::biz
            J1["«job / worker (consumidor)»\nRegistrarDamnificadoJob\n• Cola: 'empadronamiento'\n• handle(): ejecuta upsertPadron()\n• failed(): libera clave DNI en Redis"]:::job

            S1 -. "implementa (AppServiceProvider)" .-> I1
        end

        %% ==========================================
        %% 4. CAPA DE DATOS (COMPONENTE MODELO - ELOQUENT Y MIGRACIONES)
        %% ==========================================
        subgraph DATA ["4. CAPA DE DATOS (COMPONENTE MODELO — app/Models/ y database/migrations/)"]
            direction LR
            M1["«model»\nDamnificado (Eloquent)\n+ upsertPadron(array $datos): void\n+ zona(): BelongsTo\n+ brigadista(): BelongsTo"]:::data
            M2["«models relacionados»\nZona (zonas)\nUser (users — registrado_por)"]:::data
            MI1["«migration»\n2026_10_01_000003_create_damnificados_table\nPK id | UNIQUE (dni)\nFK zona_id | FK registrado_por"]:::data

            M1 -- "pertenece a" --> M2
            M1 --> MI1
        end

        %% RELACIONES ENTRE CAPAS MVC
        C1 -. "1a. GET /captura: renderiza vista" .-> V1
        C1 -. "4. POST /captura: redirección + confirmación (< 2 s)" .-> V1
        C1 -- "1b. POST /captura: invoca abstracción (DIP)" --> I1
        J1 -- "5. Persistencia idempotente: upsertPadron($datos)" --> M1
    end

    %% INFRAESTRUCTURA DE SOPORTE
    Redis[("«contenedor broker y caché»\nRedis 7 (AOF)\n• SETNX empadronamiento:dni:{dni}\n• Cola: queues:empadronamiento")]:::queue
    BD[("«contenedor base de datos»\nMySQL 8 (padron_db)\nTabla: damnificados (UNIQUE dni)")]:::db

    %% CONEXIONES EXTERNAS
    Brigadista -- "HTTPS (GET / POST)" --> R1
    V1 -. "HTML5 + Confirmación" .-> Brigadista
    S1 -- "2. SETNX dni + LPUSH job (TCP 6379 — < 5 ms)" --> Redis
    Redis -- "3. BLPOP job asíncrono (queue:work)" --> J1
    M1 -- "6. SQL INSERT ... ON DUPLICATE KEY UPDATE" --> BD
    MI1 -. "define esquema físico" .-> BD
```

---

## 3. Reglas de Dependencia entre las Capas MVC

1. **Flujo HTTP y Presentación (`Controlador → Vista`):** Las peticiones HTTP entran siempre por la capa de **Control** (`Rutas` → `Middleware auth:sanctum` + `EnsureRoleMiddleware` → `StoreDamnificadoRequest` → `EmpadronamientoController`). El controlador invoca al contrato del *service* y finalmente renderiza la vista **Blade** (`captura/create.blade.php`); la vista jamás contiene lógica de negocio ni consultas SQL.
2. **Controladores Delgados e Inversión de Dependencias (`Controlador → Interfaz del Modelo`):** `EmpadronamientoController` depende exclusivamente de la interfaz `EmpadronamientoServiceInterface` (registrada en `AppServiceProvider`): no conoce si el registro se encola en Redis o se guarda síncronamente, ni accede directamente a Eloquent.
3. **Servicios Independientes de HTTP (`Modelo - Negocio`):** `EmpadronamientoService` recibe un arreglo validado (`array $datos`), verifica el formato del DNI, reserva atómicamente la clave `empadronamiento:dni:{dni}` en **Redis** (`SETNX`) y despacha `RegistrarDamnificadoJob` a la cola `empadronamiento` en `< 5 ms`.
4. **Flujo Unidireccional en Jobs (`Modelo - Cola → Modelo - Datos`):** `RegistrarDamnificadoJob` es consumido desde Redis por el contenedor *Worker* (`queue:work`) y persiste directamente a través de `Damnificado::upsertPadron($datos)`, sin volver a invocar a `EmpadronamientoService`. Si agota sus 3 reintentos (`failed()`), libera la clave de reserva en Redis para permitir el reenvío.
5. **Integridad en Capa de Datos (`Modelo - Datos → MySQL`):** El modelo Eloquent `Damnificado` y la migración `2026_10_01_000003_create_damnificados_table.php` son la única puerta hacia **MySQL**, resguardando la restricción física `UNIQUE (dni)` y las relaciones con `Zona` y `User`.
