# Decisiones Arquitectónicas

| ID | Decisión arquitectónica | Driver relacionado | Justificación | Resultado |
|---|---|---|---|---|
| ADR-001 | Monolito modular (Laravel) | DA04 - Despliegue en contenedores | Organizar las funcionalidades en módulos internos dentro de una misma aplicación desplegable (una app web Laravel + un contenedor worker). | Módulos de Autenticación, Captura/Empadronamiento, Padrón y Reportes. |
| ADR-002 | MVC en capas con servicios | DA06 - Mantenibilidad | Separar las reglas de negocio del detalle técnico HTTP y de persistencia: los controllers solo coordinan, los services concentran las reglas, los models/Eloquent persisten. | Capas: Rutas/Middlewares → Controllers → Services → Models (Eloquent) → MySQL, con vistas Blade en presentación. |
| ADR-003 | Encolamiento de peticiones con Laravel Queues | DA01 - Rendimiento, RC05 | Encolar masivamente los registros para manejar picos de hasta 3,000 usuarios concurrentes sin saturar ni bloquear la base de datos. | Jobs de registro encolados procesados por workers independientes de la app web. |
| ADR-004 | Autenticación basada en tokens con Laravel Sanctum | DA05 - Seguridad | Proteger el dashboard y los reportes, y permitir llamadas autenticadas desde el dispositivo móvil del brigadista sin depender de cookies de sesión en entornos con conectividad intermitente. | Módulo de autenticación con emisión de tokens por usuario y middlewares de validación. |
