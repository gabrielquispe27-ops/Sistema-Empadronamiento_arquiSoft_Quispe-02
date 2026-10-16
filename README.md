# Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia

## Información del Proyecto

* **Sistema:** Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia
* **Autor / Estudiante:** Gabriel Quispe Andia
* **Curso:** Laboratorio de Arquitectura de Software (IS488)
* **Docente:** Lizbeth Jaico Quispe
* **Institución:** Universidad Nacional de San Cristóbal de Huamanga (UNSCH)
* **Caso de estudio:** Sistema Transaccional de Respuesta Rápida para el Empadronamiento en Zonas de Emergencia

## Descripción

Aplicación web transaccional para el empadronamiento rápido de personas damnificadas en zonas de emergencia. Los brigadistas capturan datos demográficos y reportan daños desde sus dispositivos móviles en campo; el sistema valida, encola y consolida el padrón para que los coordinadores de Defensa Civil analicen la información y tomen decisiones logísticas.

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Frontend | Laravel Blade + Bootstrap (Mobile-First) |
| Backend | PHP 8.x / Laravel (MVC con capa de servicios) |
| Autenticación | Laravel Sanctum (tokens) |
| Procesamiento asíncrono | Laravel Queues (jobs + workers) |
| Base de datos | MySQL (Eloquent ORM) |
| Despliegue | Contenedores (Docker/Podman) |

## Estructura de la Documentación

```
img/                        (diagramas exportados en PNG)
img/fuentes-mermaid/        (fuentes .mmd de cada diagrama)
docs/
├── 01-analisis-de-sistema/
│   ├── 01-actores.md
│   ├── 02-historias-del-usuario.md
│   ├── 03-requisitos-funcionales.md
│   ├── 04-atributos-de-calidad.md
│   ├── 05-restricciones.md
│   ├── 06-driver-arquitectonicos.md
│   ├── 07-decisiones-arquitectonicas.md
│   └── 08-escenarios-atributos-calidad.md
├── 02-arquitectura-software/
│   ├── arquitectura-inicial.md
│   ├── componentes-arquitectonicos.md
│   ├── enfoque-arquitectonico.md
│   └── estilo-arquitectonico.md
├── 03-diseno-de-software/
│   └── diseno-interno/
│       ├── diseno-interno-de-modulos.md
│       ├── patrones-de-diseno.md
│       └── principios-de-diseno.md
└── 04-modelo-c4/
    ├── Nivel1-Diagrama-de-Contexto-del-Sistema.md
    ├── Nivel2-Diagrama-de-Contenedores.md
    ├── Nivel3-Diagrama-de-Componentes.md
    └── Nivel4-Diagrama-de-Codigo.md
```
