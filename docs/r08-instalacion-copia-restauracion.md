# R08F02 - Instalacion limpia, copia y restauracion MySQL

> Evidencia tecnica local. No acredita despliegue de produccion ni entrega academica. Revision: 2026-10-05.

## Alcance probado

La prueba se ejecuto en Docker Compose con un proyecto aislado para no reutilizar los contenedores ni el volumen de la demo habitual:

- Proyecto Compose aislado: `eduquest_r08_clean`.
- Aplicacion aislada: `http://localhost:18080/login`.
- MySQL aislado: puerto local `13306`.
- Volumen aislado: `eduquest_r08_clean_sail-mysql`.
- Volumen de demo habitual observado y no usado: `eduquest_sail-mysql`.

La base de demo habitual se verifico antes y despues con los mismos conteos:

| Tabla | Antes | Despues |
|---|---:|---:|
| `users` | 7 | 7 |
| `classrooms` | 2 | 2 |
| `missions` | 10 | 10 |
| `mission_assignments` | 8 | 8 |
| `mission_enrollments` | 8 | 8 |
| `node_progress` | 10 | 10 |
| `student_reward_grants` | 10 | 10 |
| `coin_ledger_entries` | 4 | 4 |
| `avatar_profiles` | 1 | 1 |
| `student_cosmetic_items` | 1 | 1 |

## Dependencias observadas

- Docker Desktop con permiso para usar el daemon.
- Acceso de red para construir la imagen Sail si no existe cache local.
- `vendor/laravel/sail/runtimes/8.4` disponible antes de `docker compose up --build`. En un clon realmente limpio, como `vendor` no se versiona, hace falta ejecutar `composer install` con PHP/Composer local o usar otro mecanismo oficial para obtener Sail antes de construir la imagen.
- El comando `eduquest:prepare-demo` exige que exista un docente activo con `username` `carlinchis`; en esta prueba se creo un docente ficticio solo en la base aislada.
- Las credenciales locales se introducen por variables de entorno o `.env` excluido de Git. No se versionan contrasenas ni dumps.

## Instalacion limpia reproducida

Los comandos se ejecutan desde la raiz del proyecto. Las variables fuerzan puertos y volumenes separados de la demo habitual.

```powershell
$env:APP_PORT='18080'
$env:VITE_PORT='15173'
$env:FORWARD_DB_PORT='13306'
$env:DB_DATABASE='eduquest'
$env:DB_USERNAME='sail'
$env:DB_PASSWORD='<password_local_no_versionado>'

docker compose -p eduquest_r08_clean --env-file .env.example up -d --build
docker compose -p eduquest_r08_clean --env-file .env.example ps
docker volume ls --format "{{.Name}}"
```

Migraciones y catalogo:

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test php artisan migrate --force
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test php artisan db:seed --class=CosmeticCatalogSeeder --force
```

Preparacion de datos ficticios:

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test php artisan tinker --execute="`$user = App\Models\User::query()->firstOrNew(['username'=>'carlinchis']); `$user->forceFill(['name'=>'Carlinchis Demo R08', 'email'=>'carlinchis.r08@example.test', 'password'=>'<password_local_no_versionado>', 'role'=>App\Enums\UserRole::Teacher, 'active'=>true, 'must_change_password'=>false]); `$user->save();"
$env:EDUQUEST_DEMO_STUDENT_PASSWORD='<password_local_no_versionado>'
docker compose -p eduquest_r08_clean --env-file .env.example exec -T -e EDUQUEST_DEMO_STUDENT_PASSWORD=$env:EDUQUEST_DEMO_STUDENT_PASSWORD laravel.test php artisan eduquest:prepare-demo
```

Resultado real del comando de demo en la instalacion aislada:

| Dato | Valor |
|---|---:|
| Docente | `carlinchis` |
| Clase | `Clase demo EDUQuest` |
| Alumno | `alumno_demo` |
| Misiones | 5 |
| Actividades disponibles | 20 |
| XP disponible | 200 |
| Monedas disponibles | 45 |
| Asignaciones abiertas | 5 |
| Inscripciones activas | 5 |
| Progreso conservado inicial | 0 |
| XP inicial | 0 |
| Monedas iniciales | 0 |
| Compras iniciales | 0 |

Para que la restauracion pudiera comprobar progreso, recompensas e inventario, se completo en la base aislada el primer nodo disponible con el servicio real `NodeProgressService`:

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test php artisan tinker --execute="`$student = App\Models\User::query()->where('username', 'alumno_demo')->firstOrFail(); `$enrollment = `$student->missionEnrollments()->with('assignment.mission')->where('active', true)->oldest('id')->firstOrFail(); `$node = `$enrollment->assignment->mission->nodes()->orderBy('position')->firstOrFail(); app(App\Services\NodeProgressService::class)->complete(`$student, `$enrollment, `$node);"
```

Se verifico `http://localhost:18080/login` con respuesta HTTP 200.

## Copia MySQL

El dump temporal se guardo en:

```text
storage/app/private/r08-backups/eduquest-r08-clean.sql
```

`storage/app/private/.gitignore` ignora todo salvo su propio `.gitignore`, por lo que el dump queda fuera de Git.

Comandos usados:

```powershell
New-Item -ItemType Directory -Force -Path storage\app\private\r08-backups
docker compose -p eduquest_r08_clean --env-file .env.example exec -T mysql sh -c 'mysqldump --single-transaction --routines --triggers --no-tablespaces -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" > /tmp/eduquest-r08-clean.sql'
docker cp eduquest_r08_clean-mysql-1:/tmp/eduquest-r08-clean.sql storage\app\private\r08-backups\eduquest-r08-clean.sql
```

Nota: `--no-tablespaces` fue necesario porque el usuario de aplicacion no tiene privilegio global `PROCESS` en MySQL 8.4.

## Restauracion en segunda base aislada

El destino debe identificarse explicitamente. No ejecutar estos comandos contra el proyecto Compose habitual ni contra una base de demo real.

Destino probado:

```text
eduquest_r08_restore
```

Comandos usados dentro del mismo MySQL aislado `eduquest_r08_clean-mysql-1`:

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "DROP DATABASE IF EXISTS eduquest_r08_restore; CREATE DATABASE eduquest_r08_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON eduquest_r08_restore.* TO \"$MYSQL_USER\"@\"%\";"'
docker compose -p eduquest_r08_clean --env-file .env.example exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" eduquest_r08_restore < /tmp/eduquest-r08-clean.sql'
```

Para una restauracion manual futura, sustituir `eduquest_r08_restore` por otro nombre aislado y confirmar antes que no sea `eduquest` ni la base usada por la demo habitual.

## Verificacion de la restauracion

Los conteos restaurados coincidieron con la fuente aislada:

| Tabla | Origen aislado | Restaurada |
|---|---:|---:|
| `users` | 2 | 2 |
| `classrooms` | 1 | 1 |
| `missions` | 5 | 5 |
| `mission_assignments` | 5 | 5 |
| `mission_enrollments` | 5 | 5 |
| `node_progress` | 1 | 1 |
| `student_reward_grants` | 1 | 1 |
| `coin_ledger_entries` | 1 | 1 |
| `avatar_profiles` | 1 | 1 |
| `student_cosmetic_items` | 1 | 1 |
| `cosmetic_items` | 6 | 6 |

Datos concretos recuperados:

- Usuarios: `carlinchis` y `alumno_demo`.
- Clase: `Clase demo EDUQuest`.
- Primera mision: `Exploradores del sistema solar`.
- Asignaciones e inscripciones: 5 abiertas/activas en la copia fuente y en la restaurada.
- Progreso: `alumno_demo` completo `Nuestro vecindario cosmico` con 10 puntos.
- Recompensa: 10 XP y 2 monedas por el primer nodo completado.
- Inventario: `alumno_demo` conserva `character-a-inicial` equipado.

## Pendiente fuera de esta prueba

- No se ha probado un despliegue de produccion ni copias programadas en proveedor externo.
- No se ha elegido alojamiento definitivo, dominio, HTTPS, correo ni politica de retencion.
- R08 sigue necesitando capturas finales, horas reales totalizadas, anexos y revision academica.
