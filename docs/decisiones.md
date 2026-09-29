# Decisiones técnicas

## Tomadas en la propuesta

- Aplicación web monolítica en un único repositorio.
- Servidor con PHP 8.4 y Laravel 13.
- Interfaz con Vue 3, TypeScript e Inertia.
- Estilos con Tailwind CSS y shadcn-vue del starter compatible.
- Base de datos MySQL 8.4 LTS.
- Autenticación basada en sesiones, cookies y protección CSRF.
- Autorización mediante Policies de Laravel.
- Un rol por usuario: administrador, docente o alumno.
- Registro público deshabilitado.
- Alta de docentes por administrador y alta de alumnos por docente.
- Misiones con estados borrador, publicada y archivada.
- Una misión publicada no se edita directamente; se duplica para modificarla.
- Progreso y puntos calculados en servidor.
- Cuestionarios corregidos en servidor; el navegador no envía nota final válida.
- IA solo para generar borradores revisables, nunca publicación automática.
- Docker Compose será opción preferida si Docker funciona en el entorno local.

## Tomadas durante R01 y R02

- Una cuenta de alumno es global y puede tener matriculas en varias clases mediante `classroom_memberships`.
- La cuenta mantiene un unico docente creador en `users.created_by`; otro docente que matricule al alumno no puede cambiar su rol, contrasena ni estado global. La interfaz para regenerar una contrasena por el creador queda pendiente.
- Una cuenta existente se incorpora escribiendo su `username` exacto. No hay buscador, autocompletado ni vista previa global de alumnado; los datos se muestran al segundo docente solo despues de crear la matricula en una clase propia.
- La pareja clase-alumno es unica. Dar de baja cambia el estado de la matricula y conserva el registro; reincorporar reactiva la misma fila.
- `activated_at` y `deactivated_at` dejan el modelo preparado para sincronizar en el futuro las inscripciones de asignaciones, sin simularlas antes de R03-R05.
- El estado de una matricula y el estado global de la cuenta son independientes.

## Tomadas durante R03F01

- Los borradores se normalizan en `missions`, `mission_nodes`, `quiz_questions`, `quiz_options` y `flashcards`; no se guarda el editor como un unico JSON.
- La posicion de nodo es unica dentro de la mision. Subir, bajar y compactar posiciones se ejecuta en transacciones compatibles con MySQL.
- Solo se persisten nodos que superan la validacion completa de su tipo. La comprobacion independiente de preparacion vuelve a recorrer el contenido guardado para proteger una futura publicacion.
- Los proveedores de video admitidos inicialmente son YouTube y Vimeo. Se guarda el proveedor y el identificador normalizado; no se descarga la URL ni se aceptan iframes.
- El cuestionario guarda cual es la opcion correcta para la futura correccion en servidor, pero R03F01 no expone contenido a alumnos ni implementa intentos o notas.

## Tomadas durante R03F02

- Publicar bloquea el borrador, repite la comprobacion de preparacion dentro de una transaccion y fija `published_at`. El contenido publicado solo puede reutilizarse mediante una copia profunda como nuevo borrador.
- `mission_assignments` impone una pareja unica mision-clase y `mission_enrollments` una pareja unica asignacion-alumno. Las altas, bajas y reincorporaciones sincronizan inscripciones abiertas en la misma transaccion que la matricula.
- Archivar impide nuevas asignaciones sin cerrar ni borrar las existentes. Las asignaciones abiertas de una mision archivada siguen sincronizando sus matriculas.
- Retirar una asignacion sin actividad elimina la asignacion y sus inscripciones. Si existe actividad, el servicio cambia su estado a cerrada, desactiva las inscripciones y conserva las filas.
- `activity_started_at` se preparo como marcador interno para la futura actividad de R04; el primer recorrido del alumno lo activa al guardar la primera finalizacion real.

## Tomadas durante el primer recorrido de R04-R05

- `node_progress` registra una unica finalizacion por pareja inscripcion-nodo y conserva los diez puntos concedidos. El total y el porcentaje se derivan de esas filas; no se mantienen contadores editables en el usuario.
- El desbloqueo es lineal por `position`. El mapa entrega solo identificador, posicion, tipo, titulo y estado; el contenido se consulta en una ruta separada despues de repetir todos los controles de acceso.
- Una explicacion, un video o unas flashcards se completan mediante confirmacion explicita. Es una evidencia de interaccion declarada, no una acreditacion de comprension ni de visionado real.
- Un cuestionario disponible muestra preguntas y opciones mediante una proyeccion explicita que excluye `is_correct` y explicaciones. Las soluciones y explicaciones solo se envian como feedback cuando ya existe un intento guardado.
- La primera finalizacion fija `activity_started_at`. Desde ese momento, cerrar una asignacion conserva inscripciones y progreso; una asignacion sin actividad sigue pudiendo retirarse.
- No existe vista previa docente del recorrido. Las rutas de finalizacion exigen rol alumno, por lo que una futura vista previa debera permanecer separada de `node_progress`.
- `quiz_attempts` conserva nota con seis decimales, aciertos, total, resultado y fecha; `quiz_answers` conserva una opcion por pregunta. La comparacion del umbral usa los enteros de aciertos y preguntas, sin redondeo.
- La mejor nota se deriva del historial inmutable. Un aprobado crea una unica fila de `node_progress`; los reintentos posteriores, aprobados o suspensos, no cambian los diez puntos ni revocan el desbloqueo.
- Los cuestionarios admiten reintentos ilimitados funcionalmente, con un limite tecnico de cinco envios por minuto. No se persisten selecciones antes de enviar el intento completo.

## Tomadas durante R06

- El seguimiento no mantiene contadores duplicados ni modifica actividad. El porcentaje, los puntos, los intentos y la mejor nota se calculan al consultar desde `mission_enrollments`, `node_progress` y `quiz_attempts`.
- El resumen conserva y distingue inscripciones y matriculas inactivas para que una baja no borre el historial academico ya registrado.
- Los estados se derivan de la actividad persistida: sin empezar cuando no hay progreso ni intentos, en curso cuando existe alguna actividad y completada solo cuando todos los nodos tienen finalizacion.
- Un intento suspendido cuenta como actividad e intento, pero no como nodo completado. La mejor nota se obtiene de todos los intentos y no sustituye al porcentaje de avance.
- Las respuestas del seguimiento exponen del alumno solo nombre y `username`; no incluyen email, credenciales ni respuestas concretas del cuestionario.
- Clase, asignacion e inscripcion se autorizan por rol, propietario y pertenencia anidada antes de consultar sus datos.

## Tomadas durante R07

- El proveedor es OpenAI mediante la Responses API y Structured Outputs. El modelo predeterminado es `gpt-5.4-mini`, configurable con `OPENAI_MODEL`; se eligio por soporte de salida estructurada y equilibrio entre capacidad y coste para una tarea acotada.
- La aplicacion usa el cliente HTTP de Laravel y un contrato `MissionDraftProvider`; las pruebas interceptan HTTP y no dependen de red ni de una clave real.
- `OPENAI_API_KEY` solo se lee en servidor. No existe variable `VITE_*`, no se registran prompts, claves ni respuestas crudas de error, y el proveedor recibe exclusivamente los campos del formulario docente.
- La salida sigue un esquema JSON estricto y vuelve a validarse en Laravel. Se rechazan tipos, longitudes, cantidades, preguntas, opciones o respuestas correctas que no cumplan las reglas de EDUQuest.
- El limite local predeterminado es de cinco generaciones por docente y dia, una activa por docente, 45 segundos de timeout y 6000 tokens maximos de salida. El token UUID del formulario hace idempotente un doble envio.
- `ai_generations` conserva propietario, resumen de entrada sin datos de alumnos, estado, codigo de error controlado, consumo reportado y mision resultante. No almacena la clave ni mensajes internos del proveedor.
- Una respuesta valida se convierte transaccionalmente en una mision `source=ai` y `draft`. Nunca publica ni asigna y reutiliza el editor, `MissionReadiness` y el circuito de publicacion de R03.
- La IA solo propone terminos de busqueda para video. El nodo queda sin proveedor ni identificador, marcado para revision, y no puede publicarse hasta que el docente lo complete o elimine.
- Toda mision generada exige confirmacion humana. Editar despues sus datos o nodos invalida esa confirmacion y obliga a revisarla de nuevo.
- La tarifa oficial consultada para `gpt-5.4-mini` es de 0,75 USD por millon de tokens de entrada y 4,50 USD por millon de salida. No se fija coste por mision hasta medir una llamada real.

## Cuestiones abiertas

- Fechas oficiales de propuesta, 50 %, 80 % y entrega final.
- Horas oficiales del módulo y criterio de registro exigido por el centro.
- Tecnologías obligatorias o prohibidas por el centro.
- Normas sobre uso de IA generativa en el proyecto y en la memoria.
- Tutor asignado y formato definitivo de la propuesta.
- Título definitivo del proyecto y disponibilidad del nombre si se publica.
- Presupuesto o credito disponible para la API y limite economico que autorice el centro.
- Proveedor de despliegue, dominio y presupuesto real.
- Política de datos si se llegara a usar con menores reales.
- Formato exacto de evidencias que pedirá el centro.
