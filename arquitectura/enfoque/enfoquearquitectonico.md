# Enfoque Arquitectónico: Clean Architecture

## 1. Justificación del Enfoque
Para el **Sistema Transaccional de Empadronamiento**, se ha adoptado el enfoque de **Clean Architecture** (Arquitectura Limpia) dentro de cada módulo del backend. 

| Elemento | Descripción aplicada al Sistema de Empadronamiento |
| :--- | :--- |
| **Patrón / Enfoque** | Clean Architecture (Arquitectura Limpia). |
| **Objetivo** | Separar las responsabilidades y controlar las dependencias para que apunten siempre hacia las reglas de negocio (Dominio). |
| **¿Qué problema resuelve?** | Evita el acoplamiento fuerte entre la lógica de validación del padrón y las herramientas tecnológicas (Express.js, PostgreSQL o la Cola de Mensajes). Si mañana la cola de mensajes cambia de Memoria a Redis, el negocio no se altera. |
| **Capas definidas** | Presentación, Aplicación, Dominio e Infraestructura. |
| **Beneficios** | Código altamente testeable, mantenible y modular, ideal para el escenario de alta concurrencia (3,000 usuarios). |

---

## 2. Diagrama de Clean Architecture (Módulo Padrón)

```mermaid
flowchart LR
    %% ESTILOS BASADOS EN LA IMAGEN DE REFERENCIA
    classDef pres fill:#e8f4f8,stroke:#007acc,stroke-width:1px,color:#333
    classDef app fill:#e8f8e8,stroke:#2ca02c,stroke-width:1px,color:#333
    classDef dom fill:#fff8e6,stroke:#e6a800,stroke-width:1px,color:#333
    classDef infra fill:#f4e8f8,stroke:#9467bd,stroke-width:1px,color:#333
    classDef ext fill:#f9f9f9,stroke:#666,stroke-width:1px,color:#333
    classDef comp fill:#fff,stroke:#333,stroke-width:1px,color:#333

    Usuario["Actor\n(Brigadista Web/Mobile)"]:::ext

    subgraph MONOLITO ["«aplicación» Backend Empadronamiento [Node.js - Express] src/"]
        
        subgraph ADAPTADORES ["ADAPTADORES Y FRAMEWORKS - dependen de Express, pg, amqplib"]
            direction LR
            
            subgraph PRES ["PRESENTACIÓN\nsrc/padron/presentation/"]
                direction TB
                C1["«controller»\nPadronController\n(recibe JSON, envía HTTP 200)"]:::pres
                C2["«middleware»\nAuthMiddleware\n(valida JWT)"]:::pres
            end

            subgraph NUCLEO ["APLICACIÓN Y DOMINIO"]
                direction TB
                subgraph APP ["APLICACIÓN - casos de uso - src/padron/application/"]
                    direction LR
                    UC1["«caso de uso»\nRegistrarDamnificadoCasoUso\nejecutar()"]:::app
                    UC2["«caso de uso»\nProcesarColaCasoUso\nejecutar()"]:::app
                end

                subgraph DOM ["DOMINIO - núcleo - src/padron/domain/"]
                    direction LR
                    subgraph MODELOS ["Modelos (entidades y reglas)"]
                        direction TB
                        E1["«entidad»\nDamnificado\n(DNI, ubicación, daños)"]:::dom
                        E2["«reglas»\nValidacionDNI.js\n(longitud 8, numérico)"]:::dom
                    end
                    subgraph PUERTOS ["Contratos (puertos)"]
                        direction TB
                        P1["«interface»\nRepositorioPadron"]:::dom
                        P2["«interface»\nColaRecepcion"]:::dom
                    end
                end
            end

            subgraph INFRA ["INFRAESTRUCTURA\nsrc/padron/infrastructure/"]
                direction TB
                I1["«adaptador»\nRepositorioPadronPostgres\n(Consultas SQL)"]:::infra
                I2["«adaptador»\nColaRecepcionMemoria\n(Push / Pop en memoria)"]:::infra
            end
        end

        COMP["«raíz de composición»\ndependencias.js\nÚnico archivo que elige qué adaptador cumple cada contrato e inyecta en casos de uso"]:::comp
    end

    BD["«sistema externo»\nPostgreSQL\n(padron_db)"]:::ext

    %% RELACIONES
    Usuario -- "HTTP / JSON" --> PRES
    PRES -- "-invoca->" --> APP
    APP -. "depende de" .-> DOM
    
    INFRA -. "---Implementa el contrato definido\nen el dominio (inversión de dependencia)--->" .-> PUERTOS
    INFRA -- "SQL / TCP" --> BD

    COMP -. "registra" .-> PRES
    COMP -. "registra" .-> INFRA
    COMP -. "inyecta" .-> APP

    %% ORDENAMIENTO VISUAL
    PRES ~~~ INFRA