# Nivel 4 - Diagrama de Código

Detalle de las clases clave del módulo de Empadronamiento: sus atributos, métodos y relaciones (diagrama de clases UML).

```mermaid
classDiagram
    class EmpadronamientoController {
        +store(Request request)
        +index()
    }
    class EmpadronamientoServiceInterface {
        <<interface>>
        +registrar(array datos)
        +esDuplicado(string dni)
    }
    class EmpadronamientoService {
        -validarDNI(string dni)
        +registrar(array datos)
        +esDuplicado(string dni)
    }
    class RegistrarDamnificadoJob {
        +__construct(Damnificado damnificado)
        +handle(EmpadronamientoService service)
    }
    class Damnificado {
        +string dni
        +string nombres
        +string apellidos
        +int zona_id
        +string daños_materiales
        +zona() Zona
    }
    class Zona {
        +string nombre
        +string nivel_afectacion
        +damnificados() Coleccion
    }
    class AuthService {
        +login(string email, string password)
        +logout(User user)
    }

    EmpadronamientoController --> EmpadronamientoServiceInterface : invoca
    EmpadronamientoService ..|> EmpadronamientoServiceInterface : implementa
    EmpadronamientoService --> Damnificado : persiste / consulta
    EmpadronamientoService --> RegistrarDamnificadoJob : dispatch (encola)
    RegistrarDamnificadoJob --> EmpadronamientoService : handle()
    Damnificado --> Zona : pertenece a (zona_id)
    AuthController --> AuthService : invoca
    AuthService --> User : valida y emite token Sanctum
```

## Notas de implementación

* `EmpadronamientoServiceInterface` existe para poder sustituir la implementación en pruebas (principio DIP), aunque el sistema es un monolito.
* `Damnificado.dni` tiene restricción **UNIQUE** definida en su migración: es la garantía física de la regla anti-duplicados (RF03).
* `RegistrarDamnificadoJob` implementa `ShouldQueue`; el worker ejecuta `handle()` fuera del ciclo de vida de la petición HTTP.
* La autenticación usa tokens de Laravel Sanctum asociados al modelo `User`.
