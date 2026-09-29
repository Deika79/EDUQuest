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

## Diseño acordado para R09

R09F01T01 ya implementa el perfil y la seleccion inicial. El sistema de recompensas y el catalogo descritos a continuacion siguen siendo diseno pendiente.

### Separacion de magnitudes y reglas de recompensa

- Los `points_awarded` actuales siguen siendo puntos educativos de una inscripcion concreta: diez por nodo completado y usados para el seguimiento docente. No se convierten en moneda ni se gastan.
- La experiencia es global para el alumno, no gastable y se concede una sola vez por pareja alumno-nodo. La primera version concede 10 XP fijos por actividad; el docente no puede modificarlos. El nivel se deriva como `min(50, 1 + floor(total_xp / 100))`; llegar al nivel 50 no elimina la experiencia posterior.
- Las monedas son globales y gastables. Explicacion, video y flashcards las conceden al crear su primera finalizacion valida; un quiz solo al aprobarlo por primera vez. Suspensos, reintentos, revisiones de un nodo completado y reenvios no conceden moneda.
- El docente puede elegir de 0 a 3 monedas por nodo mientras la mision es borrador. La suma no puede superar 20 monedas por mision. No hay bonificacion por nota, velocidad, racha ni finalizacion total en la primera version.
- Una segunda inscripcion del mismo alumno en otra clase puede crear su propio `node_progress` y sus puntos educativos, pero no repite XP ni monedas del mismo `mission_node_id`. Una mision duplicada tiene nodos nuevos y se considera contenido nuevo; los limites y la trazabilidad docente reducen el riesgo de inflar recompensas.
- Al crear una asignacion se copian XP y monedas a una instantanea por asignacion y nodo. Publicacion e instantanea impiden que cambios de valores predeterminados, una duplicacion posterior o una nueva version alteren recompensas ya ofrecidas.

### Modelo de datos propuesto

| Tabla                             | Campos principales propuestos                                                                                                                       | Restricciones y finalidad                                                                                                                                                                                                              |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `avatar_profiles`                 | `id`, `student_id`, `character_key`, `setup_completed_at`                                                                                           | Implementada: `student_id` unico y con rol alumno; `character_key` solo admite `character-a` o `character-b`. No almacena genero, rutas, HTML, nombre publico, biografia ni fotografia. Sirve tambien como fila a bloquear al comprar. |
| `cosmetic_items`                  | `id`, `sku`, `name`, `character_key`, `collection`, `appearance_key`, `asset_key`, `coin_price`, `minimum_level`, `starter`, `active`, `sort_order` | Propuesta: `sku` y `asset_key` unicos; cada imagen completa pertenece a un personaje; precio no negativo. Un articulo inactivo se conserva para propietarios previos pero no se vende.                                                 |
| `student_cosmetic_items`          | `id`, `student_id`, `cosmetic_item_id`, `acquisition_type`, `acquired_at`                                                                           | Pareja alumno-articulo unica. Registra propiedad obtenida por seleccion inicial, compra o futura recompensa controlada. No se borra al retirar el articulo del catalogo.                                                               |
| `avatar_equipment`                | `id`, `avatar_profile_id`, `cosmetic_item_id`, `equipped_at`                                                                                        | Una fila por perfil; la apariencia completa debe ser propiedad del alumno y corresponder a su `character_key`. Comprar no crea ni cambia automaticamente esta fila.                                                                    |
| `mission_assignment_node_rewards` | `id`, `assignment_id`, `node_id`, `experience_reward`, `coin_reward`                                                                                | Pareja asignacion-nodo unica. Instantanea creada dentro de la transaccion de asignacion y nunca recalculada desde el borrador.                                                                                                         |
| `student_reward_grants`           | `id`, `student_id`, `node_id`, `first_progress_id`, `experience_awarded`, `coins_awarded`, `awarded_at`                                             | Pareja alumno-nodo unica. Une la primera finalizacion academica valida con una unica concesion global y evita premios repetidos entre inscripciones.                                                                                   |
| `coin_ledger_entries`             | `id`, `student_id`, `amount`, `reason`, `reward_grant_id`, `cosmetic_item_id`, `created_at`                                                         | Libro mayor solo de insercion; `amount` positivo para premio y negativo para compra. Referencias unicas y comprobaciones impiden dos abonos por concesion o dos debitos por la misma compra. El saldo se deriva con `SUM(amount)`.     |

`experience_total` se deriva sumando `student_reward_grants.experience_awarded`; el nivel no se persiste como valor editable. La propiedad y el movimiento de compra se crean juntos: la fila unica de `student_cosmetic_items` identifica la compra y el asiento negativo referencia ese articulo y alumno. No se aceptan saldo, precio, nivel, XP ni moneda calculados por el navegador.

### Transacciones, concurrencia y ciclo de matricula

- La finalizacion conserva el bloqueo actual de inscripcion y nodo. En la misma transaccion, intenta crear `student_reward_grants` y su abono de monedas; la restriccion unica alumno-nodo convierte dobles clics, carreras y una segunda asignacion en una sola recompensa.
- La compra bloquea `avatar_profiles`, calcula el saldo desde el libro mayor, vuelve a consultar articulo, precio, nivel y estado, e inserta propiedad y debito en una transaccion. Restricciones unicas y captura de colision devuelven una respuesta controlada, nunca saldo negativo ni error 500.
- El historial de monedas no se actualiza ni elimina. Una correccion administrativa futura exigiria un asiento compensatorio identificado, no editar el movimiento original.
- Dar de baja una matricula desactiva el acceso y la obtencion de recompensas de esa clase, pero no borra perfil, XP, monedas, propiedad ni equipamiento. Si el alumno conserva otra matricula activa puede seguir usando su perfil. Sin ninguna matricula activa, conserva todo pero no puede ganar ni gastar hasta reincorporarse.
- Reincorporar reactiva las inscripciones existentes. El progreso previo y `student_reward_grants` siguen presentes, por lo que no se conceden otra vez XP o monedas.
- Desactivar globalmente la cuenta bloquea tambien avatar, inventario y tienda mediante el middleware existente.

### Estrategia grafica y catalogo inicial

- El MVP usa seis imagenes 2D completas, no capas: personaje A y personaje B, cada uno con apariencia inicial, fantasia arcana y exploracion espacial. Las tres imagenes de cada personaje deben conservar identidad, encuadre y proporciones reconocibles.
- En el primer acceso solo se elige `character-a` o `character-b`, sin campo de genero. La apariencia inicial de nivel 1 esta incluida; no se eligen por separado tono de piel, peinado o prendas.
- El catalogo futuro contiene seis apariencias: las dos iniciales incluidas, las dos skins arcanas comprables desde nivel 2 y las dos skins espaciales comprables desde nivel 3. Alcanzar el nivel habilita la compra, pero no concede, compra ni equipa automaticamente la skin.
- Peinados, prendas intercambiables, tonos adicionales, la coleccion frontera y nuevas colecciones quedan como trabajos futuros fuera de este MVP.
- Los recursos seran originales creados para EDUQuest o encargados con cesion/licencia comercial escrita, o bien CC0/dominio publico con procedencia archivada. No se usaran licencias solo personales, `NC`, recursos extraidos de juegos ni material con autoria o licencia inciertas. Cada recurso conservara autor, fuente, licencia, fecha y version en un manifiesto.
- Los PNG maestros de 1024 x 1536 permanecen junto a los recursos recibidos y la web usa derivados WebP transparentes de 512 x 768. Cada recurso debe conservar autor, fuente, licencia, fecha y version en un manifiesto antes de una comercializacion.

### Recorrido y privacidad

- Tras cambiar la contrasena temporal, un alumno sin perfil ira a `/student/avatar/setup`. Elegira uno de los dos personajes con su apariencia inicial y despues entrara en `/student`. Las cuentas siguen creadas por docentes y `/register` permanece cerrado.
- El panel del alumno mostrara miniatura propia, nivel, XP hacia el siguiente nivel y saldo de monedas sin desplazar mision, progreso o siguiente actividad. `/student/avatar` gestionara apariencia e inventario y `/student/shop` mostrara catalogo, precio, propiedad y confirmacion de compra.
- El editor docente de mision mostrara monedas por nodo y total de la mision, con limites y explicacion de que XP y puntos no son configurables. El seguimiento puede mostrar XP, nivel y monedas concedidas por esa mision, pero no saldo disponible, compras ni inventario.
- El perfil es privado por defecto: solo el alumno ve saldo, inventario e historial de compras. Docentes no reciben catalogo adquirido ni movimientos, y otros alumnos no disponen de ruta para consultar perfiles. No hay nombre publico, fotografia, chat, ranking ni galeria de menores.
- En movil, las imagenes completas mantienen proporcion estable y los catalogos usan lista o rejilla sin desplazamiento horizontal. Selecciones y equipamiento son controles nativos o botones con nombre accesible, foco visible y estado textual; no dependen solo del color ni de arrastrar.
- `prefers-reduced-motion` elimina transiciones de equipamiento, celebraciones y movimiento ambiental. La compra y el equipamiento siguen siendo totalmente utilizables con teclado y lectores de pantalla.

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
- Confirmacion del centro sobre si R09 entra en la entrega final evaluable o queda como ampliacion posterior.
- Presupuesto y procedimiento para producir recursos graficos originales con derechos comerciales documentados.
