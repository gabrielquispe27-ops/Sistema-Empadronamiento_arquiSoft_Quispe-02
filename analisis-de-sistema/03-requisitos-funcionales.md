# Identificar Requisitos Funcionales

Traducción de las historias de usuario en funciones que el sistema debe realizar.

## Requisitos Funcionales

| ID | Requisito funcional |
|---|---|
| RF01 | El sistema debe permitir la captura de datos demográficos y reporte de daños mediante una interfaz optimizada para dispositivos móviles (Mobile-First). |
| RF02 | El sistema debe validar el formato y longitud de campos clave (como el DNI) durante el llenado de los formularios. |
| RF03 | El sistema debe evitar la duplicidad de registros del mismo damnificado, cruzando los identificadores únicos. |
| RF04 | El sistema debe encolar masivamente los registros enviados simultáneamente para procesarlos sin bloqueos en el motor de base de datos. |
| RF05 | El sistema debe mostrar un panel (dashboard) con resultados y el padrón consolidado, gestionando el acceso mediante tokens (JWT) y permisos asignados. |
| RF06 | El sistema debe emitir reportes estadísticos con la cantidad de personas empadronadas por zonas afectadas. |

## Relación entre HU y Requisitos Funcionales

| Historia de usuario | Requisitos funcionales relacionados |
|---|---|
| HU01 Registrar datos en campo | RF01, RF04 |
| HU02 Validar información | RF02, RF03 |
| HU03 Visualizar dashboard | RF05 |
| HU04 Generar reportes de avance | RF06 |