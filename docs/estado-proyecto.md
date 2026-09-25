# Estado del proyecto

Fecha de revision: 2026-09-25.

## Fase actual

- Hito autorizado por David: entrega del 50 %.
- Desarrollo iniciado con la instalacion de la base tecnica.
- Primera tarea del hito completada: aplicacion base instalada, configurada, arrancada y verificada.
- Ninguna funcionalidad R01-R08 se considera terminada.

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
- `.env` local excluido de Git y `.env.example` con valores reproducibles sin secretos reales.
- Documentacion inicial, plan maestro, `AGENTS.md` y repositorio Git local conservados.

## Verificaciones realizadas

| Verificacion | Resultado real |
|---|---|
| Contenedores | `laravel.test` y `mysql` arrancados; MySQL saludable. |
| Migraciones | Ejecutadas correctamente con `php artisan migrate --force`. |
| Pruebas Pest | `39 passed`, `137 assertions`, duracion final `48.36s`. |
| Frontend | Build de produccion correcto; `3368 modules transformed`. |
| Login | Verificado visualmente con Chrome; `GET /login` devuelve `200`. |
| Registro publico | Sin enlace visible y `GET /register` devuelve `404`. |

La primera pasada de pruebas fallo porque aun no existia `public/build/manifest.json`. Tras compilar el frontend, toda la suite paso. La ejecucion inicial como `root` dejo la cache de vistas sin escritura para el servidor `sail`; se corrigieron los permisos de `storage/` y `bootstrap/cache/` y se limpiaron las caches.

## Herramientas comprobadas

| Herramienta | Disponibilidad real |
|---|---|
| Git | Nativo: `git version 2.49.0.windows.1`. |
| Node | Nativo: `v22.15.0`. |
| PHP | No esta en el PATH de Windows. Disponible de forma reproducible en Sail: `8.4.26`. |
| Composer | No esta en el PATH de Windows. Disponible en Sail: `2.10.3`. |
| Docker | Docker Desktop `4.45.0`, cliente y motor `28.3.3`; puede ejecutar contenedores. |
| MySQL | Imagen oficial `mysql:8.4`, servidor `8.4.11`. |

El acceso denegado previo a `C:\Users\Gaylo Follen\.docker\config.json` procedia del aislamiento del entorno de ejecucion. La ACL real pertenece a `DESKTOP-LP3OBMT\David Garcia` y concede control total al usuario. No se borro ni sobrescribio la configuracion personal. Los comandos Docker funcionan al ejecutarse con acceso al contexto de Docker Desktop.

## Pendiente funcional

- Adaptar el acceso al nombre de usuario y crear los tres roles de R01.
- Implementar clases, misiones, actividades, intentos, informes y el resto de R02-R08.
- Decidir mas adelante el alcance autorizado de IA y cualquier integracion externa.
- No se han implementado equipos de WorkOS ni equipos de aplicacion.
- No hay repositorio remoto conectado ni entrega academica marcada como realizada.

## Datos pendientes del centro

- Fechas oficiales de propuesta, 50 %, 80 % y entrega final.
- Horas oficiales exigidas y formato valido para su registro.
- Tutor responsable, plantilla de memoria y criterios de evaluacion definitivos.
- Politica de uso y declaracion de IA.
- Restricciones tecnologicas o de alojamiento y formato esperado para la demo.
