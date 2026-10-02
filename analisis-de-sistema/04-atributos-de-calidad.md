# Identificar Atributos de Calidad

Determinación de las características de funcionamiento del sistema frente a escenarios de crisis.

| ID | Atributo de calidad | Escenario de calidad |
|---|---|---|
| AC01 | Rendimiento (Alta concurrencia) | El sistema debe implementar mecanismos de encolamiento para garantizar el registro sin pérdida de datos ante picos de hasta 3,000 usuarios concurrentes reportando simultáneamente. |
| AC02 | Usabilidad | La interfaz de captura debe ser intuitiva, ágil y de carga rápida (optimizada para móviles), permitiendo el trabajo dinámico de los brigadistas en campo. |
| AC03 | Integridad de Datos | El sistema debe garantizar la normalización de la base de datos cruzando identificadores únicos en tiempo real al procesar la cola, asegurando que no existan padrones alterados o duplicados. |
| AC04 | Disponibilidad | El sistema debe permanecer disponible y soportar la alta demanda mediante su estructuración y despliegue en contenedores. |
| AC05 | Seguridad | El backend debe proteger la lógica de negocio y gestionar los accesos a los reportes consolidados utilizando autenticación de usuarios mediante tokens (JWT). |