# Nivel 1 - Diagrama de Contexto del Sistema

Vista más alta: el sistema y su interacción con los actores humanos y los sistemas externos.

```mermaid
flowchart TD
    classDef person fill:#08427b,stroke:#fff,stroke-width:2px,color:#fff
    classDef system fill:#1168bd,stroke:#fff,stroke-width:2px,color:#fff
    classDef ext fill:#999999,stroke:#fff,stroke-width:2px,color:#fff

    Brigadista["Brigadista\n(usa el sistema en campo,\ndesde un dispositivo móvil)"]:::person
    Coordinador["Coordinador de Defensa Civil\n(analiza el padrón y toma decisiones)"]:::person

    Sistema["Sistema de Empadronamiento\nen Zonas de Emergencia\n[Sistema transaccional de respuesta rápida]"]:::system

    MySQL[("MySQL\n(base de datos del padrón)")]:::ext

    Brigadista -- "registra damnificados y reporta daños\nen situaciones de alta concurrencia" --> Sistema
    Coordinador -- "consulta dashboards y reportes\nconsolidados por zona" --> Sistema
    Sistema -- "persiste el padrón transaccional" --> MySQL

    style Brigadista fill:#08427b,stroke:#fff,stroke-width:2px,color:#fff
```

## Descripción

| Elemento | Tipo | Descripción |
|---|---|---|
| Brigadista | Actor humano | Registra datos demográficos y daños materiales desde campo, con conectividad inestable. |
| Coordinador de Defensa Civil | Actor humano | Consulta el padrón consolidado y los reportes de cobertura por zona. |
| Sistema de Empadronamiento | Sistema | Aplicación web Laravel que captura, valida, encola y consolida el padrón. |
| MySQL | Sistema externo | Motor relacional donde persiste el padrón único de damnificados. |
