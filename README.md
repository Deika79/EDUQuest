# EDUQuest

EDUQuest es una aplicacion web educativa para que el profesorado convierta repasos en misiones visuales, las asigne a clases y consulte el avance del alumnado.

El desarrollo avanza hacia el hito del 80 %. La base tecnica usa Laravel 13, Vue, TypeScript, Inertia, Tailwind CSS, autenticacion propia de Laravel, Pest y MySQL 8.4 mediante Docker Compose. El registro publico esta desactivado y el recorrido manual del alumno y el seguimiento docente de R06 estan implementados. La integracion de IA de R07 no se ha iniciado.

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

Cada consulta y finalizacion vuelve a comprobar cuenta, matricula, inscripcion, asignacion, mision, nodo y etapa anterior. La baja impide el acceso sin borrar progreso, y la reincorporacion lo recupera. Todavia no existe integracion de IA.

```powershell
# Pruebas focalizadas del recorrido del alumno
docker compose exec -T laravel.test php artisan test tests/Feature/Student/StudentMissionJourneyTest.php

# Pruebas focalizadas de cuestionarios
docker compose exec -T laravel.test php artisan test tests/Feature/Student/StudentQuizAttemptTest.php
```

## Seguimiento docente

El docente abre `/teacher/tracking`, elige una clase propia y una mision asignada. El resumen separa porcentaje de nodos, puntos, numero de intentos y mejor nota, y etiqueta cada recorrido como sin empezar, en curso o completado. Las matriculas e inscripciones inactivas siguen visibles como historial.

Desde `Details` se revisan los nodos completados y el historial de intentos de cada cuestionario. Para repetir el recorrido con los datos ficticios de la demo, inicia sesion como `demo50_teacher`, abre `Tracking`, elige `Aula Demo Fracciones` y su mision asignada, y entra en el detalle del alumno. Las contrasenas no se documentan.

```powershell
# Pruebas focalizadas del seguimiento docente
docker compose exec -T laravel.test php artisan test tests/Feature/Teacher/TeacherTrackingTest.php
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
