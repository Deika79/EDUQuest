# EDUQuest

EDUQuest es una aplicacion web educativa para que el profesorado convierta repasos en misiones visuales, las asigne a clases y consulte el avance del alumnado.

El desarrollo del hito del 50 % esta en curso. La base tecnica usa Laravel 13, Vue, TypeScript, Inertia, Tailwind CSS, autenticacion propia de Laravel, Pest y MySQL 8.4 mediante Docker Compose. El registro publico esta desactivado; R01 y R02 siguen su desarrollo por tareas verificables.

## Entorno local

Requisitos comprobados: Docker Desktop y Node.js/npm. PHP 8.4 y Composer se ejecutan dentro del contenedor Sail, por lo que no es necesaria una instalacion nativa.

Comandos que han funcionado en PowerShell desde la raiz del proyecto:

```powershell
# Construir y arrancar Laravel y MySQL por primera vez
docker compose up -d --build

# Arranques posteriores
docker compose up -d

# Ejecutar migraciones
docker compose exec -T laravel.test php artisan migrate --force

# Ejecutar la suite Pest
docker compose exec -T laravel.test php artisan test

# Instalar dependencias frontend para Linux y compilar produccion
docker compose exec -T laravel.test npm install --include=optional
docker compose exec -T laravel.test npm run build
```

Aplicacion local: <http://localhost:8080/login>

## Primer administrador

Con los contenedores arrancados, el administrador inicial se crea mediante un asistente interactivo. El comando solicita nombre, usuario, email, contrasena y confirmacion; la contrasena no se pasa como argumento ni se almacena en el repositorio.

```powershell
docker compose exec laravel.test php artisan eduquest:create-admin
```

El administrador puede crear y desactivar docentes desde su panel. Las altas de docentes reciben una contrasena temporal y deben cambiarla al iniciar sesion.

Los accesos de administrador, docente y alumno se comprueban sin publicar credenciales mediante las factories y pruebas automatizadas:

```powershell
docker compose exec -T laravel.test php artisan test --filter=RoleAccessTest
```

## Clases y alumnos

Un docente gestiona unicamente sus clases desde `/teacher/classes`. Puede crear una cuenta de alumno con email opcional y contrasena temporal, que debe cambiarse en el primer acceso. La contrasena solo se introduce en el formulario de alta, se transmite a Laravel y se guarda con hash; no se muestra despues ni se registra en Git.

Si la cuenta ya existe, se incorpora mediante su `username` exacto en lugar de crear otra. No existe busqueda global, autocompletado ni vista previa de cuentas ajenas. El docente que incorpora una cuenta compartida solo gestiona su matricula en esa clase; no puede modificar la cuenta ni sus credenciales. La regeneracion de contrasenas de alumnos por su docente creador queda pendiente.

Una baja desactiva la matricula sin borrar su registro ni desactivar la cuenta global. La reincorporacion reactiva esa misma matricula. La sincronizacion con asignaciones y la conservacion del avance se implementaran cuando existan esas entidades en R03-R05.

```powershell
# Pruebas focalizadas de clases, alumnos y matriculas
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/ClassroomManagementTest.php
```

## Documentacion

- `EduQuest_Plan_Maestro_DAW.md`: plan maestro original.
- `docs/rftp.md`: requisitos R01-R08, tareas y pruebas previstas.
- `docs/plan-hitos.md`: entregas y fechas pendientes.
- `docs/decisiones.md`: decisiones tecnicas y cuestiones abiertas.
- `docs/registro-horas.md`: plantilla para registrar trabajo real.
- `docs/estado-proyecto.md`: estado tecnico comprobado y trabajo pendiente.
- `docs/propuesta-resumen.md`: resumen de la propuesta academica.

El trabajo debe limitarse siempre al hito autorizado en `AGENTS.md`.
