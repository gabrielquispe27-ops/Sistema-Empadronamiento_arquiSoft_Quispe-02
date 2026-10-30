# Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia

Repositorio de documentación arquitectónica y diseño de software para el **Sistema de Empadronamiento en Zonas de Emergencia**, desarrollado como caso de estudio en el curso de Laboratorio de Arquitectura de Software.

---

## 📋 Información Académica del Proyecto

| Campo | Detalle |
| :--- | :--- |
| **Sistema / Caso de Estudio** | Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia |
| **Autor / Estudiante** | Gabriel Quispe Andia |
| **Curso** | Laboratorio de Arquitectura de Software (`IS488`) |
| **Docente** | Ing. Lizbeth Jaico Quispe |
| **Institución** | Universidad Nacional de San Cristóbal de Huamanga (UNSCH) |

---

## 🎯 Propósito del Repositorio

Este repositorio centraliza el **análisis arquitectónico, las decisiones de diseño, el modelado C4 y los artefactos gráficos** de una plataforma web transaccional orientada al registro rápido de personas damnificadas durante desastres o emergencias.

### Problemática que Resuelve
En escenarios de desastre, los **Brigadistas** desplegados en campo deben capturar datos demográficos y reportar daños materiales desde dispositivos móviles bajo condiciones de conectividad inestable y picos de alta concurrencia (hasta **3,000 usuarios simultáneos**). Simultáneamente, los **Coordinadores de Defensa Civil** requieren consultar un padrón consolidado, íntegro y sin duplicados para priorizar el envío de ayuda humanitaria y tomar decisiones logísticas oportunas.

### Objetivos delRepositorio
1. **Trazabilidad Arquitectónica:** Documentar de principio a fin cómo los actores, historias de usuario, requisitos funcionales (`RF`), atributos de calidad (`AC`) y restricciones (`RC`) dan origen a los **Drivers Arquitectónicos (`DA`)** y a las **Decisiones Arquitectónicas (`ADR`)**.
2. **Especificación del Estilo Arquitectónico:** Definir la estructura de un **Monolito Modular organizado en Capas (MVC con Capa de Servicios)** con procesamiento asíncrono mediante colas de trabajo.
3. **Modelado Estructural y Dinámico:** Proveer las vistas del **Modelo C4 (Niveles 1 al 4)**, diagramas de diseño interno y sus fuentes editables en **Mermaid (`.mmd`)** junto con sus imágenes exportadas (`.png`).

---

## 🛠️ Stack Tecnológico y Arquitectura

El sistema adopta un estilo de **Monolito Modular en Capas (MVC + Services)** bajo un esquema **Cliente-Servidor**, optimizado para alta disponibilidad en recepción de registros y mantenibilidad del dominio.

| Capa / Ámbito | Tecnología | Rol en la Arquitectura |
| :--- | :--- | :--- |
| **Presentación (Frontend)** | **Laravel Blade + Bootstrap** | Interfaz web renderizada en servidor con enfoque *Mobile-First* para formularios de captura en campo y *Dashboard* analítico para coordinadores. |
| **Control y API (Backend)** | **PHP 8.x / Laravel** | Gestión de rutas HTTP, middlewares transversales de validación y controladores ligeros sin lógica de negocio. |
| **Seguridad y Autenticación** | **Laravel Sanctum** | Autenticación basada en tokens para proteger endpoints y sesiones móviles ante caídas o intermitencias de red (`ADR-004`). |
| **Lógica de Negocio** | **Capa de Servicios (`app/Services`)** | Encapsulación de reglas de dominio: validación de DNI, control de duplicados en memoria, consolidación del padrón y cálculo de métricas por zona (`ADR-002`). |
| **Broker de Colas y Caché** | **Redis 7 (con persistencia `AOF`)** | Broker de **Laravel Queues**, deduplicación atómica de DNI (`SETNX` en $O(1)$) y caché de reportes y tokens (`ADR-003`). |
| **Procesamiento Asíncrono** | **Laravel Queues (`Jobs` + `Workers`)** | Implementación del patrón *Productor-Consumidor* (`RegistrarDamnificadoJob`) para absorber ráfagas de hasta 3,000 registros concurrentes sin saturar MySQL (`ADR-003`). |
| **Persistencia y Datos** | **MySQL 8 + Eloquent ORM** | Almacenamiento relacional normalizado (`padron_db`) con operaciones idempotentes (`upsert`) e índice físico `UNIQUE (dni)`. |
| **Infraestructura y Despliegue** | **Contenedores (Docker / Podman) + Nginx** | Balanceador **Nginx**, $N$ réplicas stateless del contenedor web Laravel, contenedor **Redis**, $M$ contenedores *worker* (`queue:work`) y servicio **MySQL** (`ADR-001`). |

---

## 📂 Organización de la Documentación Arquitectónica

El repositorio está organizado en dos directorios principales (`docs/` e `img/`), acompañados de la configuración de agentes del proyecto (`AGENTS.md`):

```text
Sistema-Empadronamiento_arquiSoft_Quispe-02/
├── AGENTS.md                                      # Definición del agente 'arquitecto-software'
├── README.md                                      # Documentación principal del repositorio
├── docs/                                          # Documentación arquitectónica por fases
│   ├── 01-analisis-de-sistema/                    # Requisitos, atributos de calidad, drivers y ADRs
│   │   ├── 01-actores.md
│   │   ├── 02-historias-del-usuario.md
│   │   ├── 03-requisitos-funcionales.md
│   │   ├── 04-atributos-de-calidad.md
│   │   ├── 05-restricciones.md
│   │   ├── 06-driver-arquitectonicos.md
│   │   ├── 07-decisiones-arquitectonicas.md
│   │   └── 08-escenarios-atributos-calidad.md
│   ├── 02-arquitectura-software/                  # Definición macro de la arquitectura
│   │   ├── arquitectura-inicial.md
│   │   ├── componentes-arquitectonicos.md
│   │   ├── enfoque-arquitectonico.md
│   │   └── estilo-arquitectonico.md
│   ├── 03-diseno-de-software/                     # Diseño detallado de módulos, patrones y SOLID
│   │   └── diseno-interno/
│   │       ├── diseno-interno-de-modulos.md
│   │       ├── patrones-de-diseno.md
│   │       └── principios-de-diseno.md
│   └── 04-modelo-c4/                              # Vistas jerárquicas del Modelo C4
│       ├── Nivel1-Diagrama-de-Contexto-del-Sistema.md
│       ├── Nivel2-Diagrama-de-Contenedores.md
│       ├── Nivel3-Diagrama-de-Componentes.md
│       └── Nivel4-Diagrama-de-Codigo.md
└── img/                                           # Recursos gráficos y fuentes de diagramas
    ├── *.png                                      # Diagramas arquitectónicos exportados en alta resolución
    └── fuentes-mermaid/                           # Código fuente editable (.mmd) de cada diagrama
        └── *.mmd
```

### 1. Análisis del Sistema (`docs/01-analisis-de-sistema/`)
Establece los cimientos funcionales y no funcionales que guían el diseño:
* [01-actores.md](docs/01-analisis-de-sistema/01-actores.md): Caracterización de los actores (*Brigadista* y *Coordinador de Defensa Civil*).
* [02-historias-del-usuario.md](docs/01-analisis-de-sistema/02-historias-del-usuario.md): Escenarios de uso desde la perspectiva de operación en campo y gestión logística.
* [03-requisitos-funcionales.md](docs/01-analisis-de-sistema/03-requisitos-funcionales.md): Especificación de capacidades de autenticación, captura, encolamiento, consolidación y reporte.
* [04-atributos-de-calidad.md](docs/01-analisis-de-sistema/04-atributos-de-calidad.md): Requisitos de rendimiento, usabilidad, integridad, disponibilidad, seguridad y mantenibilidad.
* [05-restricciones.md](docs/01-analisis-de-sistema/05-restricciones.md): Condicionantes técnicos y operativos del proyecto.
* [06-driver-arquitectonicos.md](docs/01-analisis-de-sistema/06-driver-arquitectonicos.md): Identificación de los 6 drivers críticos (`DA01` a `DA06`) que moldean la arquitectura.
* [07-decisiones-arquitectonicas.md](docs/01-analisis-de-sistema/07-decisiones-arquitectonicas.md): Registro de Decisiones de Arquitectura (`ADR-001` a `ADR-004`).
* [08-escenarios-atributos-calidad.md](docs/01-analisis-de-sistema/08-escenarios-atributos-calidad.md): Escenarios concretos (estímulo, entorno, respuesta y medida de respuesta) para evaluar la arquitectura.

### 2. Arquitectura de Software (`docs/02-arquitectura-software/`)
Describe la estructura global del sistema y la organización de sus capas:
* [arquitectura-inicial.md](docs/02-arquitectura-software/arquitectura-inicial.md): Propuesta arquitectónica base y flujo general de información.
* [enfoque-arquitectonico.md](docs/02-arquitectura-software/enfoque-arquitectonico.md): Estrategia técnica para satisfacer los drivers arquitectónicos identificados.
* [estilo-arquitectonico.md](docs/02-arquitectura-software/estilo-arquitectonico.md): Definición del **Monolito Modular en Capas (MVC + Services)**, reglas de dependencia entre capas y topología de despliegue en contenedores.
* [componentes-arquitectonicos.md](docs/02-arquitectura-software/componentes-arquitectonicos.md): Inventario de componentes Laravel (Rutas, Middlewares, Controllers, Services, Jobs, Models, Migrations, Vistas) y su interacción.

### 3. Diseño de Software (`docs/03-diseno-de-software/diseno-interno/`)
Profundiza en la construcción interna del código y las buenas prácticas de ingeniería:
* [diseno-interno-de-modulos.md](docs/03-diseno-de-software/diseno-interno/diseno-interno-de-modulos.md): División modular interna (*Auth*, *Captura/Empadronamiento*, *Padrón* y *Reportes*) y flujos de secuencia.
* [patrones-de-diseno.md](docs/03-diseno-de-software/diseno-interno/patrones-de-diseno.md): Aplicación justificada de patrones (*MVC con capa de servicios*, *Façade*, *Repository*, *Observer*, *Producer-Consumer*, *Chain of Responsibility* y *Singleton/DI*).
* [principios-de-diseno.md](docs/03-diseno-de-software/diseno-interno/principios-de-diseno.md): Aplicación práctica de los principios SOLID (incluyendo Inversión de Dependencias - DIP) dentro del ecosistema Laravel.

### 4. Modelo C4 (`docs/04-modelo-c4/`)
Descomposición jerárquica de la arquitectura mediante el estándar C4:
* [Nivel1-Diagrama-de-Contexto-del-Sistema.md](docs/04-modelo-c4/Nivel1-Diagrama-de-Contexto-del-Sistema.md): Visión general del sistema, actores humanos y base de datos externa.
* [Nivel2-Diagrama-de-Contenedores.md](docs/04-modelo-c4/Nivel2-Diagrama-de-Contenedores.md): Unidades de ejecución y almacenamiento (Navegador Web, Aplicación Web Laravel, Worker de Colas y MySQL).
* [Nivel3-Diagrama-de-Componentes.md](docs/04-modelo-c4/Nivel3-Diagrama-de-Componentes.md): Componentes internos del contenedor principal de Laravel y sus responsabilidades.
* [Nivel4-Diagrama-de-Codigo.md](docs/04-modelo-c4/Nivel4-Diagrama-de-Codigo.md): Diagrama de clases del dominio de empadronamiento y sus relaciones.

### 5. Catálogo de Diagramas (`img/` e `img/fuentes-mermaid/`)
Todos los diagramas cuentan con su imagen exportada en `img/` y su código fuente declarativo en `img/fuentes-mermaid/`:

| Diagrama | Imagen Exportada (`.png`) | Fuente Mermaid (`.mmd`) |
| :--- | :--- | :--- |
| **Arquitectura Inicial** | [arquitectura-inicial.png](img/arquitectura-inicial.png) | [arquitectura-inicial.mmd](img/fuentes-mermaid/arquitectura-inicial.mmd) |
| **Enfoque Arquitectónico** | [enfoque-arquitectonico.png](img/enfoque-arquitectonico.png) | [enfoque-arquitectonico.mmd](img/fuentes-mermaid/enfoque-arquitectonico.mmd) |
| **Estilo Arquitectónico** | [estilo-arquitectonico.png](img/estilo-arquitectonico.png) | [estilo-arquitectonico.mmd](img/fuentes-mermaid/estilo-arquitectonico.mmd) |
| **Componentes Arquitectónicos** | [componentes-arquitectonicos.png](img/componentes-arquitectonicos.png) | [componentes-arquitectonicos.mmd](img/fuentes-mermaid/componentes-arquitectonicos.mmd) |
| **C4 - Nivel 1 (Contexto)** | [nivel1-contexto.png](img/nivel1-contexto.png) | [nivel1-contexto.mmd](img/fuentes-mermaid/nivel1-contexto.mmd) |
| **C4 - Nivel 2 (Contenedores)** | [nivel2-contenedores.png](img/nivel2-contenedores.png) | [nivel2-contenedores.mmd](img/fuentes-mermaid/nivel2-contenedores.mmd) |
| **C4 - Nivel 3 (Componentes)** | [nivel3-componentes.png](img/nivel3-componentes.png) | [nivel3-componentes.mmd](img/fuentes-mermaid/nivel3-componentes.mmd) |
| **C4 - Nivel 4 (Clases / Código)** | [nivel4-clases.png](img/nivel4-clases.png) | [nivel4-clases.mmd](img/fuentes-mermaid/nivel4-clases.mmd) |
| **Principio DIP (SOLID)** | [principios-dip.png](img/principios-dip.png) | [principios-dip.mmd](img/fuentes-mermaid/principios-dip.mmd) |
| **Secuencia: Registro de Damnificado** | [secuencia-registro-damnificado.png](img/secuencia-registro-damnificado.png) | [secuencia-registro-damnificado.mmd](img/fuentes-mermaid/secuencia-registro-damnificado.mmd) |

---

## 🤖 Asistente de Arquitectura (`AGENTS.md`)

En la raíz del proyecto se incluye el archivo [AGENTS.md](AGENTS.md), que configura al agente especializado **`arquitecto-software`** con instrucciones de sistema como Arquitecto de Software Senior experto en el **Modelo C4**, **estilos arquitectónicos** y **diagramas Mermaid**, garantizando la coherencia evolutiva de esta documentación.
