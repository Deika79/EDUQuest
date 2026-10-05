# Estado del proyecto

Fecha de revision: 2026-10-05.

## Fase actual

- Hito autorizado por David: entrega del 80 %; R06 y R07 estan implementados y se ha integrado la direccion visual aprobada «Un camino, muchos mundos».
- Desarrollo iniciado con la instalacion de la base tecnica.
- Base tecnica instalada, configurada, arrancada y verificada.
- Primera parte de R01 implementada y probada; sus controles de propiedad ya cubren tambien las clases de R02.
- R02F01 y R02F02 implementadas y probadas, incluida la conservacion de progreso tras baja y reincorporacion.
- R03F01 y R03F02 implementadas y probadas, incluido el cierre con historial despues de actividad real.
- R04F01 y R04F02 implementadas y probadas para explicacion, video, flashcards y cuestionarios.
- R05F01 y R05F02 implementadas y probadas para los cuatro tipos, incluida una carrera HTTP simultanea real contra MySQL.
- R06F01 y R06F02 implementadas y probadas sobre los datos persistidos por R04 y R05.
- R07F01 y R07F02 implementadas y probadas con proveedor HTTP simulado y mediante una llamada externa real controlada; R07F01T01P01 queda superada.
- R09 esta autorizado como ampliacion; R09F01, R09F02 y el MVP funcional de R09F03 estan implementados. Permanecen pendientes las carreras HTTP simultaneas especificas de recompensas y compras.
- Documentacion de auditoria del 80 % preparada en `docs/estado-hito-80.md`, `docs/guia-demo-hito-80.md`, `docs/capturas-hito-80.md` y `docs/memoria-borrador-hito-80.md`.
- R08F02 cuenta con una prueba local aislada de instalacion limpia, copia MySQL y restauracion en una segunda base, documentada en `docs/r08-instalacion-copia-restauracion.md`.
- Identidad visual EDUQuest integrada en la landing, el acceso, la navegacion compartida y el panel del alumno.
- El feedback del 50 % no consta como entregado ni aprobado y no se ha creado la etiqueta `hito-50`.
- La demo local reproducible dispone de cinco misiones de ciencias y 20 actividades distintas; no implica que el hito del 80 % este entregado.

## Existe realmente

- Aplicacion Laravel `v13.33.0` en la raiz del repositorio.
- Starter oficial Vue con TypeScript, Inertia, Tailwind CSS, shadcn-vue y autenticacion propia de Laravel.
- Pest `v5.2.1` y pruebas incluidas por el starter.
- Docker Compose con Sail sobre PHP `8.4.26` y MySQL `8.4.11`.
- Composer `2.10.3` disponible dentro del contenedor de la aplicacion.
- Entorno local en `http://localhost:8080/login`.
- Entorno limpio aislado de R08F02 verificado en `http://localhost:18080/login` con proyecto Compose `eduquest_r08_clean`, puerto MySQL `13306` y volumen `eduquest_r08_clean_sail-mysql`.
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
- Borradores de mision propios con titulo, descripcion narrativa, asignatura, nivel, estado borrador y origen manual fijados en servidor.
- Editor de nodos de explicacion, video, cuestionario y flashcards con alta, edicion, eliminacion y orden persistente.
- Cuestionarios con 1-10 preguntas, 2-4 opciones, una correcta, explicacion y umbral de aprobado entre 1 y 100.
- Videos limitados a YouTube y Vimeo, validados por URL o identificador y almacenados como proveedor mas identificador normalizado.
- Flashcards con 1-10 pares obligatorios de anverso y reverso.
- Comprobacion de preparacion del borrador con errores concretos, reutilizada obligatoriamente al publicar.
- Policies y validaciones que impiden consultar o alterar borradores y nodos de otro docente mediante su ID.
- Ninguna ruta de borradores disponible para alumnos.
- Publicacion limitada a borradores propios preparados, con estado publicado, fecha y bloqueo de toda edicion posterior.
- Duplicacion profunda de misiones publicadas como nuevos borradores independientes, incluidos los cuatro tipos de nodo y su orden.
- Archivo de misiones sin borrar asignaciones existentes y bloqueo de nuevas asignaciones.
- Asignaciones unicas de misiones publicadas a una o varias clases propias, con estado abierto o cerrado y contadores visibles.
- Inscripciones unicas por asignacion y alumno creadas para matriculas activas y sincronizadas al matricular, dar de baja o reincorporar.
- Retirada transaccional de asignaciones sin actividad y cierre con conservacion de historial cuando existe una finalizacion real.
- Panel de alumno en `/student/missions` limitado a inscripciones activas, asignaciones abiertas y matriculas activas.
- Mapa lineal con nodos disponibles, bloqueados o completados; el servidor no envia contenido de nodos bloqueados.
- Actividades de explicacion, video YouTube/Vimeo con enlace alternativo, flashcards que exigen revisar todas las tarjetas y cuestionarios corregidos en servidor.
- Comprobaciones de cuenta, rol, matricula, inscripcion, asignacion, mision, nodo y predecesor en cada consulta o finalizacion.
- Progreso unico por inscripcion y nodo, protegido por transaccion y restriccion unica, con diez puntos no duplicables y porcentaje derivado.
- Aviso visible de que confirmar lectura, revision o tarjetas no demuestra comprension ni visionado completo.
- Preguntas y opciones del cuestionario sin `is_correct`, explicaciones ni otros indicadores de solucion antes del envio.
- Intentos y respuestas persistidos, nota precisa calculada en servidor, mejor nota derivada, feedback posterior y cinco envios por minuto.
- Un suspenso conserva el intento sin progreso; un aprobado desbloquea el siguiente nodo y concede diez puntos una sola vez. Un suspenso posterior no revoca la aprobacion.
- Selector docente de clase propia y mision asignada en `/teacher/tracking`, con resumen de todas sus inscripciones actuales e historicas.
- Resumen separado de porcentaje completado, puntos, intentos y mejor nota, con estados sin empezar, en curso y completada.
- Detalle individual por nodos e historial de intentos de cuestionario, sin exponer email, credenciales ni respuestas concretas.
- Policies y comprobaciones de pertenencia anidada para clase, asignacion e inscripcion; un docente no recibe datos de otro docente.
- Consultas de seguimiento de solo lectura: no alteran progreso, puntos, intentos ni estados de matricula o inscripcion.
- Pantalla docente `/teacher/missions/generate` con tema, asignatura, nivel, objetivos, dificultad, 4-8 nodos e indicaciones, y estado claro cuando falta la configuracion externa.
- Integracion de servidor con OpenAI Responses API y salida JSON estricta para `gpt-5.4-mini`; clave, modelo, timeout, cuota y maximo de salida configurables por entorno.
- Registro `ai_generations` por docente con estado, resumen sin datos personales, consumo, error controlado y borrador resultante; una generacion activa, cuota diaria y token idempotente.
- Validacion defensiva de toda salida antes de guardar: tipos, longitudes, 4-8 nodos, cuestionarios de 1-10 preguntas, 2-4 opciones, una correcta y flashcards completas.
- Borradores IA creados transaccionalmente sobre las entidades reales, siempre propios, sin publicacion ni asignacion automatica y editables en el flujo de R03.
- Videos IA guardados sin enlace como sugerencias pendientes; la preparacion bloquea publicacion hasta completar o retirar el nodo y confirmar revision humana.
- Llamada real R07 completada una sola vez con `gpt-5.4-mini`: creo la mision 7 como borrador propio de cinco nodos, sin asignaciones y con el video pendiente de revision.
- `.env` local excluido de Git y `.env.example` con valores reproducibles sin secretos reales.
- Documentacion inicial, plan maestro, `AGENTS.md` y repositorio Git local conservados.
- Guia reproducible y evidencias reales de la revision previa del hito en `docs/guia-demo-hito-50.md` y `docs/evidencias-hito-50.md`.
- Guia de demostracion del 80 % con el docente `carlinchis` y el alumno `alumno_demo`, sin contrasenas versionadas.
- Inventario de capturas necesarias para memoria del 80 %, separando capturas pendientes de capturas ya existentes.
- Borrador de memoria del 80 % con arquitectura, decisiones, implementacion, pruebas, trabajo pendiente y diagramas logicos actualizados.
- Landing publica en `/` con identidad EDUQuest, contenido en espanol y acceso por las rutas reales; no incluye registro, credenciales de prueba ni mensajes que presenten la IA como disponible.
- Logotipo, simbolo y favicon propios en la plantilla, el acceso y la navegacion compartida, con variantes legibles para fondos claros y oscuros.
- Portada de alumno en `/student/missions` sin ocultar la siguiente mision, el avance, los puntos ni la accion principal.
- Recursos web usados en `public/brand/`; fuentes SVG y raster originales conservados en `docs/identidad/EDUQuest_identidad_v01/`.
- Imagen principal optimizada a WebP de aproximadamente 166 KB y portada de alumno recortada y optimizada a aproximadamente 49 KB.
- Trailer integrado en `public/brand/eduquest-trailer.mp4` mediante un dialogo que mantiene el poster visible y solo asigna la fuente del video tras pulsar `Ver trailer`.
- El reproductor abre con sonido por la interaccion de la persona, usa controles nativos de reproduccion, volumen y pantalla completa, admite cierre con boton o `Escape`, detiene y libera el video al cerrar y devuelve el foco al boton de apertura. No usa reproduccion automatica ni repeticion.
- Variante web verificada: MP4 H.264 `yuv420p`, 1600 x 900, 30 fps, 24,29 segundos, inicio rapido, audio AAC estereo a 44,1 kHz y 5.513.692 bytes. El original HEVC de 26.733.433 bytes se conserva fuera de `public/` y de Git.
- Comando local `eduquest:prepare-demo` restringido a entornos local y testing. Reutiliza al docente `carlinchis`, la clase y `alumno_demo`, y prepara cinco misiones manuales publicadas con 20 nodos, cinco asignaciones abiertas y cinco inscripciones activas mediante los servicios de dominio existentes.
- La demo ofrece como maximo 200 XP y 45 monedas. El comando no crea progreso, concesiones, movimientos de monedas ni compras y conserva la contrasena, el avatar y todo el estado previo del alumno.

## Verificaciones realizadas

| Verificacion               | Resultado real                                                                                                                                                                         |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Contenedores               | `laravel.test` y `mysql` arrancados; MySQL saludable.                                                                                                                                  |
| Migraciones                | Ejecutadas correctamente con `php artisan migrate --force`.                                                                                                                            |
| Pruebas focalizadas R01    | `23 passed`, `76 assertions`.                                                                                                                                                          |
| Pruebas focalizadas R02    | `10 passed`, `70 assertions`.                                                                                                                                                          |
| Pruebas focalizadas R03F01 | `14 passed`, `100 assertions`.                                                                                                                                                         |
| Pruebas focalizadas R03F02 | `10 passed`, `77 assertions`.                                                                                                                                                          |
| Integracion R02-R03        | `33 passed`, `226 assertions`.                                                                                                                                                         |
| Recorrido alumno R04-R05   | `21 passed`, `215 assertions`.                                                                                                                                                         |
| Cuestionarios R04F02       | `11 passed`, `87 assertions`.                                                                                                                                                          |
| Regresion R02-R03 afectada | `20 passed`, `147 assertions`.                                                                                                                                                         |
| Seguridad focalizada hito  | `55 passed`, `462 assertions`: bloqueos, soluciones, IDs ajenos y propiedad docente.                                                                                                   |
| Seguimiento R06            | `3 passed`, `111 assertions`: dos alumnos, quiz suspendido/aprobado, historial y propietario.                                                                                          |
| Generacion R07 simulada    | `9 passed`, `82 assertions`: salida valida/invalida, cuota, timeout, error, idempotencia, recuperacion, borrador, revision y propietario.                                              |
| Generacion R07 real        | Una llamada mediante login, CSRF y formulario real; generacion 1 completada, mision 7 en borrador, 508 tokens de entrada y 1090 de salida.                                             |
| Perfil inicial R09F01T01   | `9 passed`, `64 assertions`: primer acceso, alumno existente, validacion, propiedad, roles, unicidad, orden tras contrasena y `/register`.                                             |
| Recompensas R09F02         | `8 passed`, `76 assertions`: cuatro actividades, quiz, repeticion, segunda asignacion, limites, instantanea, baja/reincorporacion y nivel.                                             |
| Regresion afectada R09F02  | `45 passed`, `392 assertions`: borradores, publicacion/asignacion, recorrido del alumno y cuestionarios.                                                                               |
| Catalogo y tienda R09F03   | `18 passed`, `136 assertions` junto con onboarding: seeder idempotente, compra, saldo/nivel, repeticion, rol, propiedad, personaje y equipamiento.                                     |
| Instantaneas MySQL R09F02  | 6 parejas asignacion-nodo existentes y 6 instantaneas migradas; todas conservan 10 XP y 0 monedas.                                                                                     |
| Progreso previo R09F02     | 6 progresos existentes convertidos en 6 concesiones unicas, 60 XP totales y ninguna moneda retroactiva.                                                                                |
| Interfaz de avatar R09     | Edge a 1440 x 1000 y 390 x 844: dos imagenes cargadas, sin desbordamiento; foco inicial en H1, seleccion A/B con flechas y boton enfocable.                                            |
| Tienda adaptable R09F03    | Chrome/CDP a 1440 x 1000 y 390 x 844: tres apariencias del personaje elegido, imagenes 512 x 768 y `scrollWidth = viewport = 390` en movil.                                            |
| Preparacion demo local     | Verificada de nuevo el 2026-10-05: docente `carlinchis`, clase `Clase demo EDUQuest`, alumno `alumno_demo`, 5 misiones, 20 nodos, 5 asignaciones e inscripciones; 200 XP y 45 monedas disponibles. |
| Conservacion alumno demo   | En la revision del 2026-10-05: 0 progresos, 0 XP, 0 monedas y 0 compras. No se completaron actividades ni compras con esta cuenta durante la auditoria documental.                      |
| Instalacion limpia R08F02  | Proyecto Compose aislado `eduquest_r08_clean`: `migrate --force`, `CosmeticCatalogSeeder`, docente ficticio `carlinchis`, `eduquest:prepare-demo`, HTTP 200 en `http://localhost:18080/login`. |
| Copia/restauracion R08F02  | Dump privado en `storage/app/private/r08-backups/eduquest-r08-clean.sql`; restaurado en `eduquest_r08_restore` con 2 usuarios, 1 clase, 5 misiones/asignaciones/inscripciones, 1 progreso, 1 recompensa, 1 movimiento de monedas, avatar e inventario. |
| Recorrido niveles y tienda | Cuenta aislada de testing: 20 finalizaciones por rutas reales, 200 XP, nivel 3, compra y equipamiento arcano y espacial; imagen equipada persistente en cabecera y panel tras recarga. |
| Identidad visual           | `/`, `/login` y `/student/missions` revisadas en Chrome a 1440 x 1000 y 390 x 844; sin desbordamiento horizontal ni solapes observados.                                                |
| Accesibilidad visual       | Recorrido por teclado comprobado en landing, acceso y panel de alumno; foco visible, textos alternativos y reduccion de movimiento comprobados.                                        |
| Reproduccion del trailer   | Apertura voluntaria en dialogo, sonido activo, controles, `playsinline`, ausencia de repeticion y cierre final con logo comprobados.                                                   |
| Carga bajo demanda         | Cero solicitudes al MP4 antes de pulsar `Ver trailer`; al cerrar se pausa, se elimina `src` y se libera el recurso.                                                                    |
| Accesibilidad del trailer  | Cierre por boton y `Escape`, foco contenido en el dialogo y devuelto al disparador; boton de acceso permanece visible fuera del dialogo.                                               |
| Preferencias del trailer   | Emulacion movil a 390 x 844: ancho de documento 390 px; movimiento reducido y ahorro de datos mantienen el poster sin solicitar el video.                                              |
| Landing y acceso           | Pruebas focalizadas: `8 passed`, `15 assertions`; `/` y `/login` se renderizan y la autenticacion conserva sus controles.                                                              |
| Demo completa en Chrome    | Administrador, docente y alumno ficticios; cuatro nodos; suspenso, reintento, 40 puntos y 100 %.                                                                                       |
| Concurrencia MySQL         | Dos `POST` simultaneos: dos respuestas finales HTTP 200, una fila de progreso y 10 puntos totales.                                                                                     |
| Suite Pest completa        | Ejecutada de nuevo el 2026-10-05 dentro de `composer ci:check`: `143 passed`, `1217 assertions`; 9 omitidas porque la verificacion de email de Fortify esta desactivada.                 |
| Revision documental 80 %   | Documentos `estado-hito-80`, `guia-demo-hito-80`, `capturas-hito-80` y `memoria-borrador-hito-80` preparados sin marcar entrega academica ni capturas pendientes como realizadas.     |
| Pint                       | Sin problemas de estilo en 185 archivos PHP en la ultima ejecucion.                                                                                                                    |
| PHPStan                    | Sin errores en 153 archivos analizados.                                                                                                                                                |
| TypeScript                 | `vue-tsc --noEmit` correcto.                                                                                                                                                           |
| Frontend                   | TypeScript correcto; formato en 95 archivos y lint en 77 sin avisos; build con `3407 modules transformed`.                                                                             |
| Cadena documental 80 %     | `composer ci:check` ejecutado el 2026-10-05: formato/lint frontend, `vue-tsc`, Pint, PHPStan y Pest correctos.                                                                         |
| Interfaz R07               | Revisada en Chrome en escritorio y a 390 x 844; despues, el flujo autenticado real abrio el borrador 7 en el editor con HTTP 200.                                                      |
| Login                      | Verificado visualmente con Chrome; `GET /login` devuelve `200`.                                                                                                                        |
| Registro publico           | Sin enlace visible y `GET /register` devuelve `404`.                                                                                                                                   |

La primera pasada historica de pruebas fallo porque aun no existia `public/build/manifest.json`. Tras compilar el frontend, toda la suite paso. La ejecucion inicial como `root` dejo la cache de vistas sin escritura para el servidor `sail`; se corrigieron los permisos de `storage/` y `bootstrap/cache/` y se limpiaron las caches.

En la revision del hito, el primer `composer ci:check` intento analizar un perfil temporal de Chrome creado dentro de `tmp/`. Se cerro ese navegador, se retiro exclusivamente el temporal generado y la cadena completa se repitio correctamente. La interfaz docente tambien contenia avisos obsoletos sobre actividades y progreso; se corrigieron y el build volvio a pasar.

El build de R07 pasa dentro del entorno reproducible Docker. El `node_modules` nativo de Windows no dispone actualmente del binario opcional `@voidzero-dev/vite-plus-win32-x64-msvc`, por lo que `npm run build` debe ejecutarse con el comando Docker documentado hasta reinstalar voluntariamente esas dependencias locales.

## Estado de R09

- R09F01, R09F02 y R09F03 estan definidos en [rftp.md](rftp.md), con modelo y reglas en [decisiones.md](decisiones.md) y entregas pequenas en [plan-hitos.md](plan-hitos.md).
- R09F01T01 existe realmente: tabla `avatar_profiles` con una fila unica por alumno, clave cerrada `character-a` o `character-b`, Policy, validacion, middleware de primer acceso y pantalla `/student/avatar/setup`.
- El cambio obligatorio de contrasena mantiene prioridad. Despues, cualquier alumno sin perfil, incluidas cuentas existentes, debe elegir personaje antes de abrir `/student` o sus misiones. Docentes y administradores no acceden y `/register` sigue cerrado.
- La pantalla usa las apariencias completas de los dos personajes. Los seis PNG originales miden 1024 x 1536; sus WebP transparentes de 512 x 768 pesan entre 60.896 y 79.802 bytes. El catalogo solo referencia la inicial, arcana y espacial previstas para cada personaje; los avatares adicionales recibidos quedan fuera de alcance y sin versionar.
- R09F02 existe realmente: cada nodo de borrador admite de 0 a 3 monedas y la mision un maximo de 20; cada asignacion fija 10 XP y las monedas por nodo en `mission_assignment_node_rewards`.
- La migracion conserva el historial anterior: crea instantaneas de 10 XP y 0 monedas para asignaciones existentes, y reconoce 10 XP por primera finalizacion historica alumno-nodo sin inventar monedas que nunca estuvieron configuradas.
- La primera finalizacion valida crea, dentro de la transaccion de progreso, una unica concesion global por alumno y nodo. `student_reward_grants` conserva el XP y `coin_ledger_entries` registra el abono auditable; repetir, reintentar un quiz o completar otra asignacion del mismo nodo no duplica el premio.
- Los puntos educativos permanecen separados por inscripcion. El XP y el saldo se calculan desde sus registros persistidos, y el nivel se deriva como `min(50, 1 + floor(XP / 100))`. El panel `/student/missions` muestra las tres magnitudes reales.
- La baja de matricula bloquea acceso y nuevos premios mediante las comprobaciones existentes. La reincorporacion conserva progreso, puntos, XP y monedas.
- R09F01T02 existe realmente: la miniatura equipada se comparte en panel y navegacion, y `/student/avatar` muestra inventario y catalogo solo del personaje elegido.
- R09F03 existe realmente: `cosmetic_items` define seis apariencias mediante un seeder idempotente; la inicial cuesta 0 y se entrega/equipa al elegir personaje, la arcana cuesta 6 monedas desde nivel 2 y la espacial 12 desde nivel 3.
- Comprar exige cuenta y matricula activas, nivel, saldo, articulo activo y personaje compatibles. La propiedad y el debito se crean juntos bajo transaccion, bloqueos y restricciones unicas. Comprar no equipa; equipar exige propiedad propia y no cambia XP, nivel, puntos, notas ni progreso.
- La interfaz `/student/avatar` muestra saldo, nivel, apariencia equipada y estados `Disponible`, `Sin monedas`, `Nivel insuficiente`, `Sin matricula activa` y `Comprada`. La baja conserva perfil, inventario y saldo, pero bloquea nuevas compras hasta reincorporarse.
- Las restricciones unicas y los bloqueos transaccionales de R09F02 estan implementados y el doble envio secuencial esta probado. Falta ejecutar una carrera HTTP simultanea especifica sobre XP y monedas; la prueba de concurrencia previa solo verifico progreso y puntos educativos de R05.
- La tienda y la economia virtual estaban fuera del MVP del plan maestro original. Su implementacion no cambia el estado academico de R01-R08 ni declara entregado el hito del 80 %.

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

## Operacion del seguimiento docente

- Iniciar sesion con un docente que tenga una clase y una mision asignada; los datos ficticios de la demo usan `demo50_teacher` sin publicar su contrasena.
- Abrir `/teacher/tracking`, elegir la clase y la asignacion, y pulsar `View progress`.
- Comparar estado, porcentaje, puntos, intentos y mejor nota. Pulsar `Details` para revisar nodos e intentos del alumno.
- Una baja se presenta como matricula e inscripcion inactivas, pero conserva el progreso historico.
- No se han generado capturas nuevas para R06 en esta tarea.

## Operacion de la generacion asistida

- Configurar `OPENAI_API_KEY` exclusivamente en `.env`; no usar variables `VITE_*` ni versionar la clave.
- Ejecutar `docker compose exec -T laravel.test php artisan config:clear`, iniciar sesion como docente y abrir `/teacher/missions/generate`.
- Completar el formulario y enviar una sola vez. Una respuesta valida abre el editor de `/teacher/missions/{id}` con estado borrador y sin asignaciones.
- Revisar todos los textos y respuestas. Los videos solo muestran terminos sugeridos: deben recibir un recurso YouTube o Vimeo valido, o eliminarse.
- Confirmar la revision humana y resolver todos los errores de preparacion antes de usar la publicacion normal de R03.
- La prueba real se ejecuto una sola vez el 29 de septiembre de 2026 con `gpt-5.4-mini`. Laravel registro `ai_generations.id=1`, respuesta segura `resp_069fe5317704866f016abb872a724887d2801641cf2c5c3f7f`, 508 tokens de entrada y 1090 de salida.
- La llamada creo `/teacher/missions/7`: mision propia `source=ai`, estado `draft`, cinco nodos (`explanation`, `video`, `quiz`, `flashcards`, `quiz`), un video sin proveedor ni identificador y marcado para revision, cero asignaciones y revision humana pendiente.
- La apertura autenticada del editor devolvio HTTP 200 y la comprobacion de preparacion siguio en falso. No se publico ni asigno la mision y no se copio la respuesta completa del proveedor a la documentacion.

## Identidad visual

- La landing publica presenta la marca, las misiones manuales, las actividades disponibles y el progreso que existe realmente.
- La comprobacion adaptable se realizo en Chrome con anchos de 1440 y 390 pixeles. En ambos casos el ancho del documento coincidio con el del viewport.
- En la landing se recorrio por teclado el logotipo enlazado y las llamadas a la accion; en el acceso, sus controles; y en el area del alumno, la navegacion y las acciones de mision. Los elementos modificados muestran foco visible.
- Se comprobaron `prefers-reduced-motion: reduce` y ahorro de datos: el hero mantiene el poster estatico y no solicita el MP4. El boton permite reproducirlo voluntariamente en ambos casos.
- La landing no consulta ni descarga el trailer al cargar. El elemento de video usa `preload="none"` y recibe su fuente exclusivamente al abrir el dialogo.
- El logo cinematografico permanece legible en el ultimo fotograma, comprobado durante la reproduccion a los 23,27 segundos. El dialogo no superpone otro logo sobre el video.
- En escritorio y en movil se verificaron apertura con sonido, controles nativos, cierre por boton y `Escape`, devolucion del foco, ausencia de desbordamiento y acceso principal visible en la portada.
- Los contrastes principales comprobados superan AA: ambar sobre azul noche 9,45:1, blanco sobre azul noche 16,79:1, turquesa de texto sobre blanco 5,03:1 y texto secundario sobre blanco 7,76:1.
- El paquete recibido contenia los recursos de `assets/`, pero no incluia la guia de identidad ni `vista-previa.html`. Esta ausencia queda documentada en `docs/identidad/README.md`; no se ha inventado ni publicado la vista previa.

## Pendiente funcional

- Completar la evidencia de R09F02 y R09F03 con carreras HTTP simultaneas especificas. Peinados, prendas intercambiables, tonos adicionales, los avatares adicionales recibidos y mas colecciones quedan como trabajos futuros.
- Completar R01F02 con las Policies de progreso y los demas recursos con propietario de hitos posteriores.
- Revisar R06 con datos de demo y preparar sus evidencias visuales cuando se soliciten; no se inventan capturas.
- Tomar las capturas autenticadas inventariadas en `docs/capturas-hito-80.md` desde localhost con las credenciales locales de David, sin versionar contrasenas.
- Completar el resto de R08, incluidos informes o exportaciones si el centro confirma que forman parte del alcance.
- No se han implementado equipos de WorkOS ni equipos de aplicacion.
- Revisar la demo y las evidencias con David y el centro. El feedback del 50 % no esta marcado como entregado y no se ha creado la etiqueta `hito-50`.
- Completar en R08 el recorrido con teclado y movil, las capturas finales, las horas reales y la revision academica. La instalacion limpia y la restauracion de una copia ya estan probadas en local aislado, pero no equivalen a despliegue de produccion.
- El repositorio remoto `origin/main` esta configurado; no hay entrega academica marcada como realizada.

## Datos pendientes del centro

- Fechas oficiales de propuesta, 50 %, 80 % y entrega final.
- Horas oficiales exigidas y formato valido para su registro.
- Tutor responsable, plantilla de memoria y criterios de evaluacion definitivos.
- Politica de uso y declaracion de IA.
- Restricciones tecnologicas o de alojamiento y formato esperado para la demo.
