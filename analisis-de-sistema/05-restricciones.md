# Identificar Restricciones

Condiciones tecnológicas y de proyecto que deben respetarse en la arquitectura.

| ID | Restricción | Descripción |
|---|---|---|
| RC01 | Tecnología Frontend | El módulo de captura (aplicación web) y el dashboard deben desarrollarse obligatoriamente utilizando la librería React.js. |
| RC02 | Tecnología Backend | El servidor de aplicaciones y lógica de negocio debe ejecutarse en el entorno Node.js. |
| RC03 | Motor de Base de Datos | La información del padrón transaccional debe alojarse y gestionarse en el motor relacional PostgreSQL. |
| RC04 | Despliegue e Infraestructura | El aplicativo y los servicios backend deben desplegarse utilizando tecnologías de contenedores (Docker/Podman). |
| RC05 | Hardware y Conectividad | El proyecto no contempla la provisión de dispositivos de hardware (tablets, celulares) ni planes de internet (conexión satelital o datos); esto será externo al sistema. |
