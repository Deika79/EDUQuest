# EDUQuest

EDUQuest es una aplicacion web educativa para que el profesorado convierta repasos en misiones visuales, las asigne a clases y consulte el avance del alumnado.

El desarrollo avanza hacia el hito del 80 %. La base tecnica usa Laravel 13, Vue, TypeScript, Inertia, Tailwind CSS, autenticacion propia de Laravel, Pest y MySQL 8.4 mediante Docker Compose. El registro publico esta desactivado; el recorrido del alumno, el seguimiento docente de R06, la generacion asistida de borradores de R07 y el MVP de avatares, recompensas y tienda cosmetica de R09 estan implementados. R07 tambien se verifico con una llamada externa real controlada.

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

# Cargar o actualizar el catalogo cosmetico reproducible
docker compose exec -T laravel.test php artisan db:seed --class=CosmeticCatalogSeeder --force

# Ejecutar la suite Pest
docker compose exec -T laravel.test php artisan test

# Instalar dependencias frontend para Linux y compilar produccion
docker compose exec -T laravel.test npm install --include=optional
docker compose exec -T laravel.test npm run build
```

Aplicacion local: <http://localhost:8080/login>

## Datos locales de demostracion

El comando local idempotente prepara la clase `Clase demo EDUQuest`, el alumno `alumno_demo` y cinco misiones manuales publicadas para el docente existente `carlinchis`. Reutiliza los servicios normales de publicacion, asignacion y matricula, no elimina otros datos y conserva contrasena, avatar, progreso, recompensas y compras existentes.

En la primera ejecucion solicita la contrasena del alumno mediante una entrada oculta. Tambien puede leerla desde `EDUQUEST_DEMO_STUDENT_PASSWORD` en el `.env` local, que esta excluido de Git. Las ejecuciones posteriores no cambian esa contrasena salvo que se indique expresamente `--reset-student-password`.

```powershell
# Primera preparacion o repeticion idempotente
docker compose exec laravel.test php artisan eduquest:prepare-demo

# Restablecer de forma segura la contrasena del alumno demo
docker compose exec laravel.test php artisan eduquest:prepare-demo --reset-student-password
```

Accesos locales:

- Inicio de sesion: <http://localhost:8080/login>.
- Docente `carlinchis`: <http://localhost:8080/teacher/classes> y <http://localhost:8080/teacher/missions>.
- Alumno `alumno_demo`: <http://localhost:8080/student/missions> y tienda en <http://localhost:8080/student/avatar>.

El recorrido contiene `Exploradores del sistema solar`, `Guardianes del ciclo del agua`, `Detectives de los ecosistemas`, `Viaje al interior de la Tierra` y `Laboratorio de la materia`. Son 20 actividades distintas que permiten obtener 200 XP y 45 monedas como maximo. Cada nodo concede 10 XP y entre 2 y 3 monedas al completarse validamente por primera vez. Los puntos, XP y monedas se generan exclusivamente al completar las actividades desde el recorrido real; el comando no marca progreso ni concede saldos.

Al llegar a 100 XP (nivel 2), el alumno puede comprar la apariencia arcana por 6 monedas. Al llegar a 200 XP (nivel 3), puede comprar la espacial por 12. Comprar no equipa automaticamente: la accion `Equipar` esta en la tienda. La limitacion de cuestionarios permite cinco envios por minuto, por lo que una demostracion automatizada debe respetar ese ritmo.

## Landing y trailer

La portada publica en `/` mantiene una imagen estatica y no solicita el archivo de video durante la carga inicial. El boton `Ver trailer` abre un reproductor adaptable con sonido, controles nativos de reproduccion, volumen y pantalla completa. El reproductor no repite el video; se cierra con su boton o con `Escape`, detiene la reproduccion y devuelve el foco al boton de apertura.

Se sirve `public/brand/eduquest-trailer.mp4`, una variante web H.264 de 1600 x 900 con audio AAC estereo, inicio rapido y 24,29 segundos de duracion. El original HEVC se conserva solo como fuente local excluida de Git. Las preferencias de movimiento reducido y ahorro de datos mantienen igualmente la portada fija; el video solo se carga si la persona decide abrirlo.

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

Una baja desactiva la matricula sin borrar su registro ni desactivar la cuenta global. Tambien desactiva sus inscripciones en asignaciones abiertas de la clase. La reincorporacion reactiva los mismos registros, y matricular a un alumno crea sus inscripciones en las asignaciones abiertas. El progreso ya registrado se conserva durante la baja y vuelve a estar disponible al reincorporar al alumno.

```powershell
# Pruebas focalizadas de clases, alumnos y matriculas
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/ClassroomManagementTest.php
```

## Misiones y asignaciones

Cada docente gestiona unicamente sus misiones desde `/teacher/missions`. La mision guarda titulo, descripcion narrativa, asignatura y nivel. El editor permite anadir, editar, eliminar y mover nodos con botones, conservando su orden al reabrir el borrador.

Tipos disponibles en el editor:

- Explicacion con titulo y texto.
- Video de YouTube o Vimeo mediante URL o identificador validado y normalizado en servidor.
- Cuestionario con 1-10 preguntas, 2-4 opciones por pregunta, explicacion de respuesta, umbral entre 1 y 100 y exactamente una opcion correcta.
- Conjunto de 1-10 flashcards con anverso y reverso obligatorios.

El editor calcula si el borrador esta listo y solo entonces permite publicarlo. Una mision publicada y sus nodos son inmutables; para cambiar su contenido se duplica como un nuevo borrador independiente. Una mision archivada no acepta nuevas asignaciones, pero conserva las existentes.

Una mision publicada puede asignarse a una o varias clases propias. La pareja mision-clase y la pareja asignacion-alumno son unicas. La pantalla muestra si cada asignacion esta abierta o cerrada y cuantos alumnos mantienen una inscripcion activa. Una asignacion sin actividad puede retirarse; si ya existe progreso, se cierra y conserva las inscripciones y su historial.

```powershell
# Pruebas focalizadas del editor de borradores
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/MissionDraftManagementTest.php

# Pruebas focalizadas de publicacion y asignaciones
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/MissionPublishingAndAssignmentTest.php
```

## Recorrido del alumno

El alumno accede a `/student/missions` y solo ve misiones con matricula e inscripcion activas y asignacion abierta. Cada mision presenta un mapa lineal: el primer nodo esta disponible, los siguientes se desbloquean en orden y los completados pueden abrirse de nuevo. El servidor no incluye el contenido de nodos bloqueados.

Se pueden completar explicaciones, videos de YouTube o Vimeo, conjuntos de flashcards y cuestionarios. El alumno confirma la revision de recursos y recibe diez puntos una sola vez por nodo; esta confirmacion registra una accion, pero no acredita comprension ni el visionado completo.

Los cuestionarios muestran preguntas y opciones sin indicadores de solucion antes de responder. Al enviar se guardan el intento y sus respuestas, se calcula la nota en servidor sin redondearla para comparar el umbral y se muestran feedback y explicaciones. Se permiten reintentos, con un maximo de cinco envios por minuto; la mejor nota y una aprobacion anterior se conservan. No se guardan borradores a mitad del intento.

Cada consulta y finalizacion vuelve a comprobar cuenta, matricula, inscripcion, asignacion, mision, nodo y etapa anterior. La baja impide el acceso sin borrar progreso, y la reincorporacion lo recupera. La IA solo asiste al docente al preparar borradores y no participa en el recorrido del alumno.

Los puntos educativos siguen perteneciendo a cada inscripcion. Aparte, la primera finalizacion valida de cada nodo concede 10 XP globales y las monedas configuradas por el docente entre 0 y 3, con un maximo de 20 por mision. La asignacion conserva una instantanea del premio y la pareja alumno-nodo solo puede recibirlo una vez, aunque exista en otra clase. El panel del alumno muestra XP, nivel derivado y saldo real.

```powershell
# Pruebas focalizadas del recorrido del alumno
docker compose exec -T laravel.test php artisan test tests/Feature/Student/StudentMissionJourneyTest.php

# Pruebas focalizadas de cuestionarios
docker compose exec -T laravel.test php artisan test tests/Feature/Student/StudentQuizAttemptTest.php

# Pruebas focalizadas de recompensas y niveles
docker compose exec -T laravel.test php artisan test tests/Feature/Student/StudentRewardsTest.php
```

## Avatar y tienda cosmetica

Tras cambiar la contrasena temporal, un alumno sin perfil elige `character-a` o `character-b` en `/student/avatar/setup`. Recibe gratuitamente la apariencia inicial correspondiente. En `/student/avatar` consulta su apariencia equipada, nivel, XP, saldo, inventario y las tres apariencias completas de su personaje.

El catalogo reproducible fija la apariencia arcana en 6 monedas y nivel 2, y la espacial en 12 monedas y nivel 3. Alcanzar el nivel solo habilita la compra: no regala ni equipa una apariencia. Comprar y equipar son acciones separadas y exclusivamente esteticas; no modifican puntos, XP, nivel, notas, progreso ni actividades.

Las monedas legitimas se obtienen al completar por primera vez actividades de misiones cuyo docente haya configurado entre 0 y 3 monedas por nodo. No existe saldo inicial ni comando de demo que altere el libro mayor. Una baja de matricula conserva perfil, inventario y saldo, pero impide comprar hasta la reincorporacion.

```powershell
# Pruebas focalizadas de primer acceso y tienda
docker compose exec -T laravel.test php artisan test tests/Feature/Student/AvatarOnboardingTest.php tests/Feature/Student/AvatarShopTest.php
```

## Seguimiento docente

El docente abre `/teacher/tracking`, elige una clase propia y una mision asignada. El resumen separa porcentaje de nodos, puntos, numero de intentos y mejor nota, y etiqueta cada recorrido como sin empezar, en curso o completado. Las matriculas e inscripciones inactivas siguen visibles como historial.

Desde `Details` se revisan los nodos completados y el historial de intentos de cada cuestionario. Para repetir el recorrido con los datos ficticios de la demo, inicia sesion como `demo50_teacher`, abre `Tracking`, elige `Aula Demo Fracciones` y su mision asignada, y entra en el detalle del alumno. Las contrasenas no se documentan.

```powershell
# Pruebas focalizadas del seguimiento docente
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/TeacherTrackingTest.php
```

## Generacion asistida de misiones

El docente abre `/teacher/missions/generate`, indica tema, asignatura, nivel, objetivos, dificultad, numero de nodos e instrucciones breves. Una respuesta valida crea una mision propia con `source=ai` y estado `draft`, y abre el editor habitual. Nunca publica ni asigna contenido.

El proveedor elegido es OpenAI mediante la Responses API y Structured Outputs, con `gpt-5.4-mini` como modelo predeterminado. La clave solo se lee en servidor. Configuracion local minima:

```dotenv
OPENAI_API_KEY=valor_local_no_versionado
```

Tras editar `.env`, limpiar la configuracion y realizar una unica prueba real:

```powershell
docker compose exec -T laravel.test php artisan config:clear
```

Inicia sesion como docente, abre <http://localhost:8080/teacher/missions/generate>, completa el formulario y pulsa una vez `Generar borrador`. Verifica que se abre `/teacher/missions/{id}`, que el estado sigue siendo borrador y que no existe ninguna asignacion. No publiques una credencial ni la introduzcas en variables `VITE_*`.

Los limites predeterminados son cinco solicitudes por docente y dia, una solicitud activa por docente, 45 segundos de timeout y 6000 tokens maximos de salida. El formulario admite 4-8 nodos y hasta 1500 caracteres para objetivos y otras indicaciones. Los videos solo reciben terminos de busqueda: el docente debe seleccionar y verificar el recurso. Ademas, toda mision generada exige confirmar revision humana antes de publicar.

La tarifa oficial consultada para `gpt-5.4-mini` es de 0,75 USD por millon de tokens de entrada y 4,50 USD por millon de tokens de salida. La revision R07 registro una llamada real controlada el 29 de septiembre de 2026 con 508 tokens de entrada y 1090 de salida; no se fija un coste general por mision porque depende de cada peticion y de la tarifa vigente. Referencias: [modelo GPT-5.4 mini](https://developers.openai.com/api/docs/models/gpt-5.4-mini) y [Structured Outputs](https://developers.openai.com/api/docs/guides/structured-outputs).

```powershell
# Pruebas focalizadas de R07 con HTTP simulado
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/AiMissionGenerationTest.php
```

## Documentacion

- `EduQuest_Plan_Maestro_DAW.md`: plan maestro original.
- `docs/rftp.md`: requisitos R01-R08, tareas y pruebas previstas.
- `docs/plan-hitos.md`: entregas y fechas pendientes.
- `docs/decisiones.md`: decisiones tecnicas y cuestiones abiertas.
- `docs/registro-horas.md`: plantilla para registrar trabajo real.
- `docs/estado-proyecto.md`: estado tecnico comprobado y trabajo pendiente.
- `docs/propuesta-resumen.md`: resumen de la propuesta academica.
- `docs/estado-hito-80.md`: auditoria de requisitos implementados, parciales y pendientes del 80 %.
- `docs/guia-demo-hito-80.md`: guion de demo de 10-15 minutos con acciones que modifican datos.
- `docs/capturas-hito-80.md`: inventario de capturas necesarias y pendientes.
- `docs/memoria-borrador-hito-80.md`: borrador de memoria actualizado con diagramas logicos del 80 %.

El trabajo debe limitarse siempre al hito autorizado en `AGENTS.md`.
