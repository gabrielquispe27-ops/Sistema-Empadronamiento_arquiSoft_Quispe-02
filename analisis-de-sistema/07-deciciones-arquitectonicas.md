# Decisiones Arquitectónicas

| ID | Decisión arquitectónica | Driver relacionado | Justificación | Resultado |
|---|---|---|---|---|
| ADR-001 | Monolito modular | DA04- Despliegue en contenedores; DA06- Mantenibilidad | Organizar las funcionalidades en módulos independientes dentro de una misma aplicación desplegable (Backend Node.js). | Módulos de Recepción, Validación, Reportes y Autenticación. |
| ADR-002 | Clean Architecture | DA06- Mantenibilidad | Separar las reglas del negocio de los detalles tecnológicos (como Express, PostgreSQL y la cola de mensajes). | Capas de Dominio, Aplicación, Infraestructura y Presentación. |
| ADR-003 | Encolamiento de peticiones (Cola de mensajes) | DA01- Rendimiento | Encolar masivamente los registros para manejar picos de hasta 3,000 usuarios concurrentes sin saturar ni bloquear la base de datos. | Módulo de recepción asíncrono implementado en la capa de infraestructura. |
| ADR-004 | Autenticación basada en tokens (JWT) | DA05- Seguridad | Proteger los endpoints de la API y gestionar el acceso de los coordinadores al dashboard sin guardar estado en el servidor. | Módulo y middleware de seguridad para la validación de tokens JWT. |