# Diseño Interno de Módulos

Detalle interno de los módulos del sistema: qué componentes contiene cada uno y cómo se reparten las responsabilidades entre controllers, services, jobs y models.

## 1. Módulo Autenticación

| Elemento | Archivo | Responsabilidad |
|---|---|---|
| Controller | `app/Http/Controllers/AuthController.php` | Login/logout y emisión del token de acceso. |
| Service | `app/Services/AuthService.php` | Valida credenciales y crea/revoca tokens de Laravel Sanctum. |
| Model | `app/Models/User.php` | Usuario del sistema (brigadista o coordinador) con su rol. |
| Vistas | `resources/views/auth/login.blade.php` | Formulario de acceso. |

## 2. Módulo Captura / Empadronamiento

| Elemento | Archivo | Responsabilidad |
|---|---|---|
| Controller | `app/Http/Controllers/EmpadronamientoController.php` | Muestra el formulario y recibe el registro. |
| Service | `app/Services/EmpadronamientoService.php` | Valida datos demográficos, detecta duplicados por DNI y encola el registro. |
| Job | `app/Jobs/RegistrarDamnificadoJob.php` | Unidad encolable que persiste el registro (procesada por el worker). |
| Model | `app/Models/Damnificado.php` | Entidad del padrón con sus relaciones (zona, daños reportados). |
| Vistas | `resources/views/captura/create.blade.php` | Formulario mobile-first con Bootstrap. |

## 3. Módulo Padrón

| Elemento | Archivo | Responsabilidad |
|---|---|---|
| Controller | `app/Http/Controllers/PadronController.php` | Expone el padrón consolidado al dashboard. |
| Service | `app/Services/PadronService.php` | Consolida registros y consulta el padrón por zonas. |
| Model | `app/Models/Zona.php` | Zona afectada con su cobertura. |

## 4. Módulo Reportes

| Elemento | Archivo | Responsabilidad |
|---|---|---|
| Controller | `app/Http/Controllers/ReporteController.php` | Sirve los reportes estadísticos al coordinador. |
| Service | `app/Services/ReporteService.php` | Calcula métricas (empadronados por zona, avance de cobertura). |
| Vistas | `resources/views/reportes/*.blade.php` | Tablas y gráficos del dashboard. |

## 5. Esquema de datos base (MySQL)

| Tabla | Clave | Relaciones |
|---|---|---|
| `users` | PK `id` | Posee tokens de Sanctum; rol brigadista/coordinador. |
| `zonas` | PK `id` | Tiene muchos `damnificados`. |
| `damnificados` | PK `id`, **UK `dni`** | Pertenece a una `zona`; almacena datos demográficos y daños reportados. |

> La clave única sobre `dni` es el soporte físico de la regla de negocio anti-duplicados (RF03), junto con la validación previa del service.

## 6. Reglas de diseño interno

1. Un controller no puede consultar Eloquent directamente: siempre pasa por el service de su módulo.
2. Un service no debe llamar a otro service de otro módulo exponiendo sus modelos: la coordinación entre módulos se resuelve en el controller o mediante jobs/eventos.
3. Los jobs son delgados: contienen solo la orquestación del registro, delegando las reglas en el service.
4. Las migraciones son la única fuente de verdad del esquema; los modelos solo lo reflejan.
