# Estilo Arquitectónico

## 1. Definición del Estilo Global

Para el Sistema Transaccional de Respuesta Rápida para el Empadronamiento, se ha seleccionado un estilo arquitectónico de **Monolito Modular organizado en Capas (MVC con servicios)**, operando bajo un modelo **Cliente-Servidor** y con presentación en servidor.

*   **Cliente-Servidor:** Separa claramente la interfaz de usuario (vistas Blade servidas por HTTP) del procesamiento centralizado (Laravel + MySQL).
*   **Monolito Modular:** Todas las funcionalidades se despliegan como una sola unidad (la aplicación Laravel), pero internamente el código está dividido por módulos de negocio (Auth, Captura/Empadronamiento, Padrón y Reportes).
*   **Arquitectura en Capas (MVC + servicios):** Dentro del monolito, las responsabilidades se dividen en Presentación (Blade), Control (Rutas, Middlewares, Controllers), Lógica de Negocio (Services y Jobs) y Datos (Models/Eloquent + MySQL).

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
    classDef db fill:#f9f9f9,stroke:#333,stroke-width:1px;

    %% ACTORES Y FRONTEND
    Brigadista["Brigadista"]:::actor
    Coordinador["Coordinador de Defensa Civil"]:::actor

    subgraph FRONTEND ["Cliente Web (Navegador móvil / escritorio)"]
        UI["HTML / CSS / JS\n[Bootstrap Mobile-First + Dashboard]"]:::frontend
    end

    Brigadista --> UI
    Coordinador --> UI

    %% BACKEND MONOLITO
    subgraph BACKEND ["«monolito» Laravel Empadronamiento [PHP 8 - Laravel]"]
        direction TB

        MW["Rutas + Middlewares (transversales)\nauth:sanctum - validación de entrada - manejo de errores"]:::infra

        %% CAPA 1 - PRESENTACIÓN
        subgraph PRESENTACION ["1. CAPA DE PRESENTACIÓN (Renderiza vistas en el servidor)"]
            direction LR
            V_Cap["módulo captura\n(captura/create.blade.php)"]:::capa1
            V_Dash["módulo dashboard\n(dashboard/index.blade.php)"]:::capa1
            V_Rep["módulo reportes\n(reportes/*.blade.php)"]:::capa1
        end

        %% CAPA 2 - CONTROL
        subgraph CONTROL ["2. CAPA DE CONTROL (Recibe peticiones, delega en services)"]
            direction LR
            C_Auth["AuthController\n(login, logout, token)"]:::capa2
            C_Cap["EmpadronamientoController"]:::capa2
            C_Pad["PadronController"]:::capa2
            C_Rep["ReporteController"]:::capa2
        end

        %% CAPA 3 - NEGOCIO
        subgraph NEGOCIO ["3. CAPA DE LÓGICA DE NEGOCIO (Reglas de negocio y coordinación)"]
            direction LR
            S_Auth["AuthService\n(valida credenciales, emite token Sanctum)"]:::capa3
            S_Cap["EmpadronamientoService\n(validación, duplicados, encolamiento)"]:::capa3
            S_Pad["PadronService\n(consolidación del padrón)"]:::capa3
            S_Rep["ReporteService\n(métricas por zona)"]:::capa3
        end

        %% COLA
        subgraph COLA ["PROCESAMIENTO ASÍNCRONO (Laravel Queues)"]
            direction LR
            J_Cap["RegistrarDamnificadoJob\n(worker independiente)"]:::infra
        end

        %% CAPA 4 - DATOS
        subgraph DATOS ["4. CAPA DE DATOS (Persistencia con Eloquent)"]
            direction LR
            M_User["User\n(Eloquent)"]:::capa1
            M_Dam["Damnificado\n(Eloquent)"]:::capa1
            M_Zona["Zona\n(Eloquent)"]:::capa1
            MI["migrations\n(clave única DNI, claves foráneas)"]:::capa1
        end

        %% CONEXIONES INTERNAS
        UI -- "HTTPS" --> MW
        MW --> PRESENTACION
        V_Cap --> C_Cap
        V_Dash --> C_Pad
        V_Rep --> C_Rep

        C_Auth --> S_Auth
        C_Cap --> S_Cap
        C_Pad --> S_Pad
        C_Rep --> S_Rep

        S_Cap -.->|"encola registro"| J_Cap
        J_Cap --> M_Dam
        S_Cap --> M_Dam
        S_Pad --> M_Dam
        S_Rep --> M_Zona
        S_Auth --> M_User
        M_Dam --> MI
    end

    %% BASE DE DATOS
    DB[("MySQL\n(padron_db)")]:::db

    %% CONEXIONES EXTERNAS
    M_Dam -- "SQL - TCP 3306" --> DB
    M_Zona -- "SQL - TCP 3306" --> DB
    M_User -- "SQL - TCP 3306" --> DB

    %% REGLAS
    subgraph LEYENDA ["Reglas de la arquitectura"]
        R1["1. Cada capa solo invoca a la capa inmediatamente inferior.
2. Un módulo no accede a los models ni a las tablas de otro módulo sin pasar por su service.
3. La comunicación dentro de un módulo se hace llamando a su service.
4. Toda la lógica de negocio vive en services, jamás en controllers ni en vistas Blade."]
    end
```

## 3. Despliegue

Aunque el código forma un solo monolito Laravel, el despliegue en contenedores separa al menos dos procesos:

* **Contenedor web:** atiende las peticiones HTTP (rutas, controllers, vistas Blade).
* **Contenedor worker:** ejecuta `php artisan queue:work` para procesar los jobs de registro masivo.
* **MySQL:** motor relacional externo (contenedor o servicio gestionado), comunicado por TCP 3306.
