# Estado del proyecto

Fecha de revision: 2026-09-25.

## Fase actual

- Hito autorizado por David: entrega del 50 %.
- Desarrollo iniciado con la instalacion de la base tecnica.
- Base tecnica instalada, configurada, arrancada y verificada.
- Primera parte de R01 implementada y probada; sus controles de propiedad ya cubren tambien las clases de R02.
- R02F01 implementada y probada. R02F02 permanece parcial hasta que existan asignaciones y progreso.

## Existe realmente

- Aplicacion Laravel `v13.33.0` en la raiz del repositorio.
- Starter oficial Vue con TypeScript, Inertia, Tailwind CSS, shadcn-vue y autenticacion propia de Laravel.
- Pest `v5.2.1` y pruebas incluidas por el starter.
- Docker Compose con Sail sobre PHP `8.4.26` y MySQL `8.4.11`.
- Composer `2.10.3` disponible dentro del contenedor de la aplicacion.
- Entorno local en `http://localhost:8080/login`.
- Migraciones base aplicadas sobre la base MySQL `eduquest`.
- Registro publico desactivado en Fortify; `GET /register` y `POST /register` no estan disponibles.
- Pantalla y enlaces de alta publica retirados del frontend.
- Usuarios con `username` unico, rol limitado a administrador, docente o alumno, estado activo y marca de cambio obligatorio de contrasena.
- Email nullable para alumnos y unico cuando existe.
- Login por `username` con limitacion de intentos; recuperacion por email conservada.
- Middleware que cierra una sesion abierta cuando la cuenta pasa a inactiva.
- Comando interactivo `eduquest:create-admin`, sin credenciales fijas.
- Panel de administrador para crear, activar y desactivar docentes, protegido por Policy y validacion cerrada.
- Paneles basicos separados para administrador, docente y alumno.
- Cambio obligatorio de la contrasena temporal antes de acceder al panel del rol.
- Clases con un unico docente propietario, listado y edicion restringidos mediante Policy y consultas por propietario.
- Cuentas de alumno creadas por docentes con `username` unico, email opcional, contrasena temporal y rol fijado en servidor.
- Incorporacion de una cuenta existente por `username` exacto, sin busqueda ni vista previa global y sin duplicar usuarios.
- Matriculas unicas por clase y alumno, con estado, fecha de activacion y fecha de baja independientes del estado global de la cuenta.
- Baja y reincorporacion sobre el mismo registro de matricula, sin borrado.
- Pantallas docentes de listado de clases y detalle con edicion, alumnado y estado de matriculas.
- `.env` local excluido de Git y `.env.example` con valores reproducibles sin secretos reales.
- Documentacion inicial, plan maestro, `AGENTS.md` y repositorio Git local conservados.

## Verificaciones realizadas

| Verificacion            | Resultado real                                                                                             |
| ----------------------- | ---------------------------------------------------------------------------------------------------------- |
| Contenedores            | `laravel.test` y `mysql` arrancados; MySQL saludable.                                                      |
| Migraciones             | Ejecutadas correctamente con `php artisan migrate --force`.                                                |
| Pruebas focalizadas R01 | `23 passed`, `76 assertions`.                                                                              |
| Pruebas focalizadas R02 | `10 passed`, `70 assertions`.                                                                              |
| Suite Pest completa     | `57 passed`, `247 assertions`; 9 omitidas porque la verificacion de email de Fortify esta desactivada.     |
| PHPStan                 | Sin errores en 65 archivos analizados.                                                                     |
| TypeScript              | `vue-tsc --noEmit` correcto.                                                                               |
| Frontend                | TypeScript correcto; formato en 79 archivos y lint en 66 sin avisos; build con `3383 modules transformed`. |
| Login                   | Verificado visualmente con Chrome; `GET /login` devuelve `200`.                                            |
| Registro publico        | Sin enlace visible y `GET /register` devuelve `404`.                                                       |

La primera pasada de pruebas fallo porque aun no existia `public/build/manifest.json`. Tras compilar el frontend, toda la suite paso. La ejecucion inicial como `root` dejo la cache de vistas sin escritura para el servidor `sail`; se corrigieron los permisos de `storage/` y `bootstrap/cache/` y se limpiaron las caches.

## Herramientas comprobadas

| Herramienta | Disponibilidad real                                                                |
| ----------- | ---------------------------------------------------------------------------------- |
| Git         | Nativo: `git version 2.49.0.windows.1`.                                            |
| Node        | Nativo: `v22.15.0`.                                                                |
| PHP         | No esta en el PATH de Windows. Disponible de forma reproducible en Sail: `8.4.26`. |
| Composer    | No esta en el PATH de Windows. Disponible en Sail: `2.10.3`.                       |
| Docker      | Docker Desktop `4.45.0`, cliente y motor `28.3.3`; puede ejecutar contenedores.    |
| MySQL       | Imagen oficial `mysql:8.4`, servidor `8.4.11`.                                     |

El acceso denegado previo a `C:\Users\Gaylo Follen\.docker\config.json` procedia del aislamiento del entorno de ejecucion. La ACL real pertenece a `DESKTOP-LP3OBMT\David Garcia` y concede control total al usuario. No se borro ni sobrescribio la configuracion personal. Los comandos Docker funcionan al ejecutarse con acceso al contexto de Docker Desktop.

## Operacion de cuentas

Crear el primer administrador sin exponer la contrasena en argumentos ni archivos:

```powershell
docker compose exec laravel.test php artisan eduquest:create-admin
```

El comando solicita los datos y la contrasena de forma interactiva. Los tres paneles se comprueban con factories, sin credenciales publicadas:

```powershell
docker compose exec -T laravel.test php artisan test --filter=RoleAccessTest
```

## Operacion de clases y alumnos

- El docente accede a `/teacher/classes`; todas las consultas y escrituras comprueban rol y propietario en servidor.
- Una cuenta nueva recibe una contrasena temporal introducida en un campo protegido y almacenada con hash. El alumno debe cambiarla al iniciar sesion.
- Para una cuenta ya existente se exige el `username` exacto entregado por el alumno o su responsable. No se exponen listados, busquedas ni datos previos de cuentas gestionadas por otros docentes.
- Un docente que incorpora una cuenta compartida solo controla su matricula y no puede modificar la cuenta ni sus credenciales. La regeneracion de contrasena por el docente creador queda pendiente.
- La baja no borra la matricula ni desactiva la cuenta; la reincorporacion reutiliza el mismo registro.

## Pendiente funcional

- Completar R01F02 al existir misiones y los demas recursos con propietario de hitos posteriores.
- Completar R02F02 al existir asignaciones y avance: sincronizar sus inscripciones al dar de alta o baja una matricula y probar que el historial se conserva.
- Implementar misiones, actividades, intentos, informes y el resto de R03-R08.
- Decidir mas adelante el alcance autorizado de IA y cualquier integracion externa.
- No se han implementado equipos de WorkOS ni equipos de aplicacion.
- El repositorio remoto `origin/main` esta configurado; no hay entrega academica marcada como realizada.

## Datos pendientes del centro

- Fechas oficiales de propuesta, 50 %, 80 % y entrega final.
- Horas oficiales exigidas y formato valido para su registro.
- Tutor responsable, plantilla de memoria y criterios de evaluacion definitivos.
- Politica de uso y declaracion de IA.
- Restricciones tecnologicas o de alojamiento y formato esperado para la demo.
