# Identificar Drivers Arquitectónicos

Elementos que influyen de manera importante en el diseño de las capas del sistema.

| ID | Driver arquitectónico | Origen | ¿Por qué influye en la arquitectura? |
|---|---|---|---|
| DA01 | El sistema debe encolar en memoria peticiones de hasta 3,000 usuarios concurrentes. | AC01 - Rendimiento | Obliga a implementar el "Módulo de Recepción (Cola de Mensajes)" en la capa de negocio antes de acceder a la base de datos. |
| DA02 | El frontend debe ser responsivo (Mobile-First) construido en React.js. | RC01 - Frontend, AC02 - Usabilidad | Define la tecnología de la capa de presentación y su naturaleza como aplicación que consumirá servicios externos. |
| DA03 | La base de datos debe ser relacional utilizando PostgreSQL. | RC03 - Base de Datos, AC03 - Integridad | Condiciona a la capa de datos a utilizar una estructura normalizada con tablas relacionales para zonas y damnificados. |
| DA04 | El despliegue de toda la solución técnica debe basarse en contenedores. | RC04 - Infraestructura, AC04 - Disponibilidad | Limita e influye en la estrategia sobre cómo las tres capas (Presentación, Negocio, Datos) se van a interconectar y empaquetar. |
| DA05 | La plataforma debe gestionar accesos a través de tokens JWT. | AC05 - Seguridad | Impacta en los mecanismos de comunicación autorizada entre el frontend y el backend de Node.js. |