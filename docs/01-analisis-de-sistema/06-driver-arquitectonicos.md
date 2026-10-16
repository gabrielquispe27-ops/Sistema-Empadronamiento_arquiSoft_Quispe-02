# Identificar Drivers Arquitectónicos

Elementos que influyen de manera importante en el diseño de las capas del sistema.

| ID | Driver arquitectónico | Origen | ¿Por qué influye en la arquitectura? |
|---|---|---|---|
| DA01 | El sistema debe encolar peticiones de registro de hasta 3,000 usuarios concurrentes. | AC01 - Rendimiento, RC05 - Procesamiento Asíncrono | Obliga a implementar un módulo de recepción asíncrono (Laravel Queues + jobs) entre la capa de negocio y la base de datos. |
| DA02 | La interfaz de captura debe ser responsiva (Mobile-First) construida con Blade + Bootstrap. | RC01 - Frontend, AC02 - Usabilidad | Define la tecnología de la capa de presentación y obliga a que las vistas se rendericen livianas para dispositivos móviles en campo. |
| DA03 | La base de datos debe ser relacional utilizando MySQL. | RC03 - Base de Datos, AC03 - Integridad | Condiciona a la capa de datos a utilizar Eloquent sobre una estructura normalizada (zonas, damnificados, usuarios). |
| DA04 | El despliegue de la aplicación y de los workers de la cola debe basarse en contenedores. | RC06 - Infraestructura, AC04 - Disponibilidad | Define cómo se empaquetan e interconectan la app web y el proceso worker de la cola (contenedores separados). |
| DA05 | La plataforma debe gestionar accesos mediante tokens de Laravel Sanctum. | RC04 - Autenticación, AC05 - Seguridad | Impacta en los middlewares de autenticación/autorización y en cómo las vistas y endpoints validan al usuario. |
| DA06 | El código debe ser mantenible y testeable, con reglas de negocio separadas del detalle HTTP. | AC06 - Mantenibilidad | Justifica la separación en capas (Controllers → Services → Models) dentro del monolito Laravel. |
