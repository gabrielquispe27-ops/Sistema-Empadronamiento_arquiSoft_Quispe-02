# Identificar Restricciones

Condiciones tecnológicas y de proyecto que deben respetarse en la arquitectura.

| ID | Restricción | Descripción |
|---|---|---|
| RC01 | Tecnología Frontend | El módulo de captura (mobile-first) y el dashboard deben desarrollarse con vistas **Blade** del framework Laravel, estilizadas con **Bootstrap**. |
| RC02 | Tecnología Backend | Toda la lógica de servidor debe ejecutarse sobre **PHP 8.x / Laravel**, organizado como MVC con una capa intermedia de servicios. |
| RC03 | Motor de Base de Datos | La información del padrón transaccional debe alojarse y gestionarse en el motor relacional **MySQL**. |
| RC04 | Autenticación | El acceso a los módulos protegidos (dashboard, reportes) debe gestionarse mediante tokens de **Laravel Sanctum** emitidos por usuario autenticado. |
| RC05 | Procesamiento Asíncrono | El registro masivo de damnificados debe procesarse mediante **Laravel Queues** (jobs + workers) para no saturar el motor de base de datos. |
| RC06 | Despliegue e Infraestructura | El aplicativo y sus workers de cola deben poder desplegarse de forma reproducible utilizando tecnologías de contenedores (Docker/Podman). |
| RC07 | Hardware y Conectividad | El proyecto no contempla la provisión de dispositivos de hardware (tablets, celulares) ni planes de internet (conexión satelital o datos); esto será externo al sistema. |
