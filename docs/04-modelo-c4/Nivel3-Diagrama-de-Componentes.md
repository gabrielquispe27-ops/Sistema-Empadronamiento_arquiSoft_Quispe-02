# Nivel 3 - Diagrama de Componentes

Internos del contenedor "App Web Laravel": los componentes de las capas MVC con servicios y su relación con el worker de la cola.

```mermaid
flowchart TD
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef ctrl fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef biz fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef data fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef ext fill:#999999,stroke:#fff,stroke-width:2px,color:#fff

    subgraph PRES ["Capa de Presentación (Blade + Bootstrap)"]
        V_Cap["captura/create.blade.php\n(formulario mobile-first)"]:::pres
        V_Dash["dashboard/index.blade.php\n(consolidado del padrón)"]:::pres
    end

    subgraph CTRL ["Capa de Control (app/Http)"]
        MW["Middleware auth:sanctum\n(valida token y permisos)"]:::ctrl
        C_Auth["AuthController"]:::ctrl
        C_Cap["EmpadronamientoController"]:::ctrl
        C_Pad["PadronController"]:::ctrl
        C_Rep["ReporteController"]:::ctrl
    end

    subgraph BIZ ["Capa de Negocio (app/Services + app/Jobs)"]
        S_Auth["AuthService\n(emite/revoca tokens)"]:::biz
        S_Cap["EmpadronamientoService\n(valida, anti-duplicados, encola)"]:::biz
        S_Pad["PadronService\n(consolidación)"]:::biz
        S_Rep["ReporteService\n(métricas por zona)"]:::biz
        J_Reg["RegistrarDamnificadoJob\n(unidad encolable)"]:::biz
    end

    subgraph DATA ["Capa de Datos (app/Models + database)"]
        M_User["User"]:::data
        M_Dam["Damnificado"]:::data
        M_Zona["Zona"]:::data
    end

    DB[("MySQL\npadron_db")]:::ext

    V_Cap -- "POST /captura" --> MW
    V_Dash -- "GET /dashboard" --> MW
    MW --> C_Cap
    MW --> C_Pad
    MW --> C_Rep

    C_Auth --> S_Auth
    C_Cap --> S_Cap
    C_Pad --> S_Pad
    C_Rep --> S_Rep

    S_Cap -. "dispatch()" .-> J_Reg
    S_Cap --> M_Dam
    S_Pad --> M_Dam
    S_Pad --> M_Zona
    S_Rep --> M_Zona
    S_Auth --> M_User
    J_Reg --> M_Dam
    M_Dam --> DB
    M_Zona --> DB
    M_User --> DB
```

## Componentes y dependencias

| Componente | Depende de | No debe depender de |
|---|---|---|
| Vistas Blade | Controllers (datos enviados) | Models, MySQL |
| Middlewares | Tokens Sanctum | Services |
| Controllers | Services de su módulo | Eloquent directo, cola |
| Services | Models, Jobs | HTTP, vistas Blade |
| Jobs | Services/Models | HTTP, vistas Blade |
| Models | MySQL (Eloquent) | Controllers, Services |
