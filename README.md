# EDUQuest

EDUQuest es una aplicacion web educativa para que el profesorado convierta repasos en misiones visuales, las asigne a clases y consulte el avance del alumnado.

El desarrollo del hito del 50 % ha comenzado. La base tecnica ya usa Laravel 13, Vue, TypeScript, Inertia, Tailwind CSS, autenticacion propia de Laravel, Pest y MySQL 8.4 mediante Docker Compose. El registro publico esta desactivado. Ninguna funcionalidad R01-R08 se considera terminada todavia.

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

## Documentacion

- `EduQuest_Plan_Maestro_DAW.md`: plan maestro original.
- `docs/rftp.md`: requisitos R01-R08, tareas y pruebas previstas.
- `docs/plan-hitos.md`: entregas y fechas pendientes.
- `docs/decisiones.md`: decisiones tecnicas y cuestiones abiertas.
- `docs/registro-horas.md`: plantilla para registrar trabajo real.
- `docs/estado-proyecto.md`: estado tecnico comprobado y trabajo pendiente.
- `docs/propuesta-resumen.md`: resumen de la propuesta academica.

El trabajo debe limitarse siempre al hito autorizado en `AGENTS.md`.
