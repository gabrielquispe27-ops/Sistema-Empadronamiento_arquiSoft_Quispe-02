# Estilo Arquitectónico

## 1. Definición del Estilo Global
Para el Sistema Transaccional de Respuesta Rápida para el Empadronamiento, se ha seleccionado un estilo arquitectónico de **Monolito Modular organizado en Capas** con despliegue en contenedores, operando bajo un modelo **Cliente-Servidor**.

*   **Cliente-Servidor:** Separa claramente la interfaz de usuario (React.js) de la lógica de procesamiento centralizada (Node.js).
*   **Monolito Modular:** Todas las funcionalidades del backend se despliegan como una sola unidad (un solo contenedor de Node.js), pero internamente el código está fuertemente dividido por dominios de negocio (módulos de Auth, Recepción, Padrón y Reportes).
*   **Arquitectura en Capas:** Dentro de cada módulo, las responsabilidades se dividen en Presentación, Lógica de Negocio y Datos.

## 2. Diagrama de la Arquitectura Inicial (Vista Estructural)

```mermaid
flowchart TD
    %% ESTILOS
    classDef actor fill:#f9f9f9,stroke:#333,stroke-width:1px;
    classDef frontend fill:#fff,stroke:#333,stroke-width:1px;
    classDef capa1 fill:#e6f2ff,stroke:#66a3ff,stroke-width:1px;
    classDef capa2 fill:#e6ffe6,stroke:#66cc66,stroke-width:1px;
    classDef capa3 fill:#fff2e6,stroke:#ff9933,stroke-width:1px;
    classDef middleware fill:#d9e6f2,stroke:#99b3cc,stroke-width:1px;
    classDef db fill:#f9f9f9,stroke:#333,stroke-width:1px;

    %% ACTORES Y FRONTEND
    Brigadista["Brigadista"]:::actor
    Coordinador["Coordinador de Defensa Civil"]:::actor

    subgraph FRONTEND ["Cliente Web (React.js)"]
        UI["Navegador - HTML / CSS / JavaScript\n[Mobile-First & Dashboard]"]:::frontend
    end

    Brigadista --> UI
    Coordinador --> UI

    %% BACKEND MONOLITO
    subgraph BACKEND ["«monolito» Backend Empadronamiento [Node.js 20 LTS - Express]"]
        direction TB
        
        MW["Middlewares Express (transversales)\ncors - express.json() - auth (JWT) - validación de entrada - manejo de errores"]:::middleware

        %% CAPA 1
        subgraph PRESENTACION ["1. CAPA DE PRESENTACIÓN (Recibe peticiones, valida entrada, responde JSON)"]
            direction LR
            P_Auth["módulo auth\n(auth.routes.js)\n(auth.controller.js)"]:::capa1
            P_Rec["módulo recepcion\n(recepcion.routes.js)\n(recepcion.controller.js)"]:::capa1
            P_Pad["módulo padron\n(padron.routes.js)\n(padron.controller.js)"]:::capa1
            P_Rep["módulo reportes\n(reportes.routes.js)\n(reportes.controller.js)"]:::capa1
        end

        %% CAPA 2
        subgraph LOGICA ["2. CAPA DE LÓGICA DE NEGOCIO (Reglas de negocio y coordinación)"]
            direction LR
            L_Auth["auth.service.js\n(login, generación JWT)"]:::capa2
            L_Rec["recepcion.service.js\n(encolamiento masivo)"]:::capa2
            L_Pad["padron.service.js\n(validación, registro)"]:::capa2
            L_Rep["reportes.service.js\n(consolidación, métricas)"]:::capa2
        end

        %% CAPA 3
        subgraph DATOS ["3. CAPA DE DATOS (Persistencia y consultas a BD)"]
            direction LR
            D_Auth["auth.repository.js"]:::capa3
            D_Rec["recepcion.repository.js\n(Gestión de Cola)"]:::capa3
            D_Pad["padron.repository.js"]:::capa3
            D_Rep["reportes.repository.js"]:::capa3
        end

        ORM["Acceso a datos compartido\nSequelize o Prisma (ORM) - modelos - pool de conexiones (src/shared/db)"]:::capa3

        %% CONEXIONES INTERNAS
        MW --> PRESENTACION
        P_Auth --> L_Auth
        P_Rec --> L_Rec
        P_Pad --> L_Pad
        P_Rep --> L_Rep

        L_Auth --> D_Auth
        L_Rec --> D_Rec
        L_Pad --> D_Pad
        L_Rep --> D_Rep

        D_Auth --> ORM
        D_Pad --> ORM
        D_Rep --> ORM
        
        %% Interacción entre módulos (servicios)
        L_Rec -.->|Procesa encolados| L_Pad
    end

    %% BASE DE DATOS
    DB[("PostgreSQL\n(padron_db)")]:::db

    %% CONEXIONES EXTERNAS
    UI -- "HTTPS / JSON\n/api/v1/" --> MW
    ORM -- "SQL - TCP 5432" --> DB

    %% REGLAS
    subgraph LEYENDA ["Reglas de la arquitectura"]
        R1["1. Cada capa solo invoca a la capa inmediatamente inferior.\n2. Un módulo no accede al repository ni a tablas de otro módulo.\n3. La comunicación entre módulos se hace llamando a su service.\n4. Todo se ejecuta en un único proceso Node.js con una única BD."]
    end