# Escenarios de Atributos de Calidad

Desglose de los atributos de calidad (doc 04) en escenarios concretos con estímulo, respuesta esperada y métrica de aceptación.

## AC01 - Rendimiento (Alta concurrencia)

| Campo | Descripción |
|---|---|
| **Estímulo** | Hasta 3,000 brigadistas envían registros de empadronamiento en el mismo lapso (pico de una emergencia). |
| **Artefacto afectado** | Módulo de Captura + cola de registro (Laravel Queues) + base de datos MySQL. |
| **Ambiente** | Producción, dispositivos móviles con conectividad inestable. |
| **Respuesta** | El sistema acepta el registro, lo encola y responde al usuario de inmediato; los jobs se procesan en segundo plano sin pérdida. |
| **Métrica** | Ningún registro perdido; tiempo de respuesta del formulario < 2 s; el backlog de la cola se vacía en < 10 min tras el pico. |

## AC02 - Usabilidad

| Campo | Descripción |
|---|---|
| **Estímulo** | Brigadista con poca experiencia técnica usa el formulario de captura en un celular en campo. |
| **Artefacto afectado** | Vistas Blade del módulo de captura (Bootstrap Mobile-First). |
| **Ambiente** | Campo, pantalla pequeña, luz solar. |
| **Respuesta** | El formulario se completa en pocos pasos, con validación inmediata de campos clave (DNI, zona). |
| **Métrica** | Registro completo en menos de 2 minutos por damnificado; cero capacitación adicional más allá de una guía de una página. |

## AC03 - Integridad de Datos

| Campo | Descripción |
|---|---|
| **Estímulo** | Dos brigadistas registran a la misma persona (mismo DNI) desde zonas distintas. |
| **Artefacto afectado** | Jobs de procesamiento + modelos Eloquent + esquema MySQL. |
| **Ambiente** | Producción, procesamiento asíncrono. |
| **Respuesta** | El job detecta el duplicado por identificador único y descarta o marca el registro para revisión. |
| **Métrica** | 0 duplicados persistidos en el padrón consolidado. |

## AC04 - Disponibilidad

| Campo | Descripción |
|---|---|
| **Estímulo** | Fallo o reinicio de un contenedor (app web o worker) durante una jornada de empadronamiento. |
| **Artefacto afectado** | Despliegue en contenedores (app Laravel + workers). |
| **Ambiente** | Producción, orquestador de contenedores. |
| **Respuesta** | El contenedor se reinicia automáticamente y los jobs encolados no se pierden. |
| **Métrica** | Disponibilidad objetivo 99.5%; cero jobs perdidos por reinicio. |

## AC05 - Seguridad

| Campo | Descripción |
|---|---|
| **Estímulo** | Un usuario no autenticado intenta acceder al dashboard o a los reportes consolidados. |
| **Artefacto afectado** | Middleware de autenticación (Laravel Sanctum) + rutas protegidas. |
| **Ambiente** | Producción, acceso vía HTTP. |
| **Respuesta** | El sistema rechaza la petición con código 401 y no expone datos del padrón. |
| **Métrica** | 0 accesos no autorizados; tokens revocables por usuario. |

## AC06 - Mantenibilidad

| Campo | Descripción |
|---|---|
| **Estímulo** | Se debe cambiar la regla de validación del DNI o agregar una nueva zona afectada. |
| **Artefacto afectado** | Services y Models del módulo de Empadronamiento. |
| **Ambiente** | Desarrollo. |
| **Respuesta** | El cambio se realiza en el service/model correspondiente sin tocar controllers ni vistas de otros módulos, verificado por tests automatizados. |
