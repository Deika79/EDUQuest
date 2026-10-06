# RFTP - Requisitos, funciones, tareas y pruebas

Estado general: hito del 80 % en desarrollo, con R06 y la implementacion de R07 autorizadas. Solo se actualizan como ejecutadas las tareas y pruebas verificadas.

## R01 Solo deben acceder personas autorizadas y cada una debe tener los permisos de su perfil.

- **R01F01** Gestionar credenciales y cuentas.
- **R01F01T01** Adaptar acceso por usuario, altas controladas, cambio y recuperación de contraseña, desactivación y cierre de sesión. Estado: ejecutada para administrador y docentes; las altas de alumnos corresponden a R02.
- **R01F01T01P01** Probar acceso válido e inválido, recuperación docente y bloqueo de una cuenta desactivada con sesión previa. Estado: ejecutada y superada con Pest.

- **R01F02** Aplicar roles y propiedad.
- **R01F02T01** Crear Policies y validación de campos para impedir elevación de rol y acceso a recursos ajenos. Estado: parcial; aplicadas a docentes, clases, matriculas y borradores de mision, pendiente extenderlas a los recursos futuros.
- **R01F02T01P01** Comprobar que un alumno no entra en docencia y que un docente no modifica una clase ajena. Estado: ejecutada y superada con Pest para roles y clases.

## R02 El profesor debe organizar a sus alumnos en clases.

- **R02F01** Gestionar clases y alumnos.
- **R02F01T01** Crear formularios y relaciones para clases propias, cuentas de alumnos y matrículas activas. Estado: ejecutada; incluye cuentas compartidas por `username` exacto sin duplicarlas.
- **R02F01T01P01** Crear dos clases con propietarios distintos y verificar separación de sus alumnos. Estado: ejecutada y superada con Pest, incluidos ID ajeno, campos manipulados y matricula duplicada.

- **R02F02** Gestionar altas y bajas.
- **R02F02T01** Sincronizar matrículas con inscripciones en asignaciones abiertas, conservando historial al desactivar. Estado: ejecutada; altas, bajas y reincorporaciones crean, desactivan o reactivan la misma inscripcion mediante transacciones y restricciones unicas.
- **R02F02T01P01** Comprobar que una baja bloquea el acceso y que una reincorporación conserva el avance previo. Estado: ejecutada y superada con Pest; la baja bloquea las rutas del alumno y la reincorporacion reutiliza la inscripcion con su progreso y puntos previos.

## R03 El profesor debe poder preparar y distribuir una misión.

- **R03F01** Editar y validar borradores.
- **R03F01T01** Crear editor de misión y ordenación de nodos; validar contenido antes de publicar. Estado: ejecutada para borradores manuales con explicacion, video, cuestionario y flashcards.
- **R03F01T01P01** Guardar, reabrir y reordenar un borrador; rechazar publicación con nodos incompletos. Estado: superada; Pest verifica persistencia, reapertura, orden, validacion de contenido y rechazo efectivo de una publicacion incompleta.

- **R03F02** Publicar y asignar contenido.
- **R03F02T01** Publicar, duplicar, archivar y asignar misiones a clases propias; impedir edición de publicadas. Estado: ejecutada; incluye inscripciones sincronizadas y retirada de asignaciones sin actividad.
- **R03F02T01P01** Asignar a dos clases propias; rechazar una clase ajena y verificar que una copia no altera el original. Estado: superada; tambien se prueban publicacion invalida, inmutabilidad, IDs anidados, duplicados, archivo, altas, bajas y reincorporaciones. R04 registra actividad real y ya verifica que el cierre conserva inscripciones y progreso.

## R04 El alumno debe realizar actividades de repaso de varios tipos.

- **R04F01** Presentar recursos y tarjetas.
- **R04F01T01** Implementar explicación, vídeo validado con enlace alternativo y flashcards accesibles. Estado: ejecutada con confirmacion explicita y aviso de que la accion no acredita comprension ni visionado completo.
- **R04F01T01P01** Completar cada tipo; comprobar rechazo de vídeo inválido y de tarjetas ajenas al nodo. Estado: ejecutada y superada con Pest; la validacion de video se cubre en el editor y las tarjetas se contrastan en servidor.

- **R04F02** Corregir cuestionarios.
- **R04F02T01** Validar preguntas y opciones, corregir en servidor y guardar intentos y mejor nota. Estado: ejecutada; no expone soluciones antes de responder, compara la nota sin redondear, conserva todos los intentos y limita a cinco envios por minuto.
- **R04F02T01P01** Probar 2 de 3 aciertos con umbral del 70 %, respuestas manipuladas y reintento posterior. Estado: ejecutada y superada con Pest; incluye preguntas omitidas o duplicadas, IDs ajenos, nodo bloqueado, baja, aprobacion seguida de suspenso, feedback y puntos unicos. R04 queda implementado en su alcance previsto.

## R05 El alumno debe avanzar por etapas sin perder lo que ha completado.

- **R05F01** Aplicar los desbloqueos.
- **R05F01T01** Comprobar matrícula, asignación y nodo anterior al consultar o finalizar una actividad. Estado: ejecutada; incluye cuenta e inscripcion activas, asignacion abierta, pertenencia a la mision y rol de alumno en cada peticion.
- **R05F01T01P01** Intentar abrir y completar un nodo bloqueado por URL y petición directa; debe rechazarse. Estado: ejecutada y superada con Pest, incluidos IDs de otra inscripcion o mision; un cuestionario no desbloquea el siguiente nodo hasta aprobarse.
- **R05F01T02** Ampliacion visual posterior al hito del 80 %: permitir que el docente elija un escenario ilustrado de mision (`fantasy`, `science` u `old_west`) y presentar el recorrido del alumno sobre la imagen correspondiente, manteniendo un unico camino secuencial por el orden real de los nodos. Estado: ejecutada; las misiones existentes conservan compatibilidad mediante escenario por defecto, los borradores IA quedan editables antes de publicar y el mapa usa puntos relativos deterministas por mision, sin azar por renderizado ni bifurcaciones.
- **R05F01T02P01** Probar seleccion, persistencia, duplicacion y exposicion del escenario, estabilidad del recorrido y conservacion de permisos de nodos bloqueados. Estado: superada en pruebas automatizadas focalizadas; quedan pendientes solo las capturas visuales autenticadas en navegador grafico de una mision por escenario, porque el navegador integrado de Codex no pudo abrir la sesion en esta ejecucion.

- **R05F02** Persistir progreso y puntos.
- **R05F02T01** Guardar una finalización por inscripción y nodo, con transacción y unicidad; calcular avance. Estado: ejecutada para los cuatro tipos; aprobar un cuestionario reutiliza la finalizacion idempotente, concede diez puntos una vez y el porcentaje se calcula desde finalizaciones persistidas.
- **R05F02T01P01** Reenviar y duplicar simultáneamente la finalización; verificar un único premio y persistencia tras nuevo acceso. Estado: ejecutada y superada. Ademas de los reenvios secuenciales y la persistencia tras baja y reincorporacion, una carrera real de dos peticiones HTTP simultaneas contra MySQL termino con dos respuestas controladas, una sola fila de progreso y diez puntos totales.

## R06 El profesor debe conocer el avance de sus alumnos.

- **R06F01** Mostrar seguimiento individual y de clase.
- **R06F01T01** Consultar progreso, intentos y mejor nota filtrando por profesor, clase y asignación. Estado: ejecutada; el resumen de clase y el detalle individual se calculan desde inscripciones, finalizaciones e intentos persistidos, incluidos los historicos inactivos.
- **R06F01T01P01** Comparar el panel con resultados de dos alumnos conocidos y denegar datos de otro docente. Estado: ejecutada y superada con Pest; se verifican dos alumnos con recorridos distintos, datos personales minimos y rechazo de clase, asignacion o inscripcion ajenas o mal anidadas.

- **R06F02** Distinguir avance de calificación.
- **R06F02T01** Presentar porcentaje completado, nota y estados con etiquetas y filtros comprensibles. Estado: ejecutada; progreso, puntos, intentos y mejor nota se muestran por separado, junto con los estados sin empezar, en curso y completada.
- **R06F02T01P01** Verificar que un alumno con recursos vistos y quiz pendiente no figura como misión completada. Estado: ejecutada y superada con Pest; un quiz suspendido queda pendiente, mientras que un aprobado posterior conserva la mejor nota y permite completar la mision.

## R07 El profesor debe poder recibir ayuda de IA sin publicar contenido sin revisar.

- **R07F01** Generar un borrador estructurado.
- **R07F01T01** Crear servicio de API, esquema de salida, cuota, timeout y validación del resultado. Estado: ejecutada con OpenAI Responses API, Structured Outputs, `gpt-5.4-mini` configurable, cinco solicitudes diarias por docente, una solicitud activa, token idempotente, timeout de 45 segundos y validacion de servidor antes de persistir.
- **R07F01T01P01** Probar salida válida, salida inválida, cuota y error del proveedor; demostrar una llamada real. Estado: ejecutada y superada; Pest cubre salida valida e invalida, cuota, timeout, error HTTP, idempotencia y recuperacion tras una interrupcion con llamadas externas simuladas. El 29 de septiembre de 2026 se completo ademas una unica llamada real mediante el formulario autenticado con `gpt-5.4-mini`: la generacion `ai_generations.id=1`, identificada por el proveedor como `resp_069fe5317704866f016abb872a724887d2801641cf2c5c3f7f`, creo la mision 7 como borrador propio editable, sin publicarla ni asignarla.

- **R07F02** Revisar antes de publicar.
- **R07F02T01** Convertir la salida válida en borrador editable y exigir completar o retirar vídeos pendientes. Estado: ejecutada; usa las entidades y el editor de R03, deja los videos sin URL ni identificador y exige tanto resolver sus avisos como confirmar revision humana.
- **R07F02T01P01** Comprobar que generar no publica y que solo el propietario puede revisar y publicar. Estado: ejecutada y superada con Pest; el borrador conserva propietario, origen IA y estado borrador, no crea asignaciones, bloquea publicacion antes de revisar y deniega acceso y confirmacion a otro docente.

## R08 El proyecto debe poder instalarse, probarse y explicarse con evidencias.

- **R08F01** Diseñar y documentar la solución.
- **R08F01T01** Mantener RFTP, casos de uso, diagramas, planificación, horas reales y memoria por hitos. Estado: parcial; RFTP, estado, guia de demo, inventario de capturas, diagramas logicos del 80 %, borrador de memoria del 80 % y evidencia local de instalacion/copia/restauracion estan actualizados. Horas reales, capturas finales y cierre academico siguen pendientes.
- **R08F01T01P01** Contrastar tareas cerradas con commits y evidencias, y totalizar horas y desviaciones. Estado: parcial; funciones R01-R07 y la ampliacion R09 estan contrastadas con codigo, pruebas y estado documental. No se inventan horas ni capturas autenticadas. Falta la revision academica y la totalizacion cuando existan registros reales.

- **R08F02** Verificar e instalar la aplicación.
- **R08F02T01** Preparar pruebas críticas, interfaz adaptable, README, contenedores, datos de demo y copia/restauración de la BD. Estado: parcial; pruebas criticas, contenedores, README, datos ficticios de demo e instalacion/copia/restauracion local de MySQL estan disponibles. El procedimiento queda documentado en `docs/r08-instalacion-copia-restauracion.md`; el despliegue definitivo, copias programadas y retencion en produccion siguen pendientes.
- **R08F02T01P01** Ejecutar pruebas, recorrer la demo con teclado y móvil, instalar desde cero y restaurar una copia. Estado: parcial; recorrido completo verificado en Chrome de escritorio, cadena de calidad superada, instalacion limpia aislada reproducida con Docker Compose, copia MySQL creada y restaurada en `eduquest_r08_restore`, y revision de uso movil/teclado registrada en `docs/r08f02-revision-uso-movil-teclado.md`. Se corrigieron desbordamiento del login movil, foco de quiz/confirmacion, migas en cabecera movil y tablas de seguimiento. La captura autenticada de pantallas internas y una pasada manual completa con navegador grafico siguen pendientes por limitacion del navegador de esta sesion.

## R09 El alumno debe poder personalizar una identidad visual y obtener recompensas cosméticas sin alterar su evaluación.

R09 es una ampliacion autorizada por David despues del alcance original. El MVP simplificado de dos personajes, recompensas, catalogo, compras y equipamiento esta implementado. La carrera HTTP simultanea especifica de recompensas y compras se verifico en MySQL aislado y queda documentada en `docs/r09-concurrencia-http.md`.

- **R09F01** Elegir y personalizar un avatar 2D en el primer acceso.
- **R09F01T01** Crear el perfil de avatar exclusivo del alumno y el desvio de primer acceso, posterior al cambio de contrasena temporal, sin habilitar registro publico. El alumno elige `character-a` o `character-b`; se guarda solo esa clave estable, sin genero ni campos independientes de tono, peinado o prendas, y se usa la apariencia completa inicial de nivel 1. Estado: implementada.
- **R09F01T01P01** Probar que solo un alumno autenticado configura su perfil unico, que no altera otro perfil ni envia claves o rutas no admitidas, que un alumno existente entra en el mismo flujo, que el cambio de contrasena ocurre antes y que `/register` permanece bloqueado. Estado: superada; `9 passed`, `64 assertions`, migracion MySQL aplicada y recorrido real verificado en 1440 x 1000 y 390 x 844 con teclado y foco visible.
- **R09F01T02** Mostrar el personaje elegido de forma coherente en el area del alumno y preparar la seleccion de apariencias completas compatibles con ese mismo personaje, sin compositor por capas. Estado: implementada; la apariencia equipada aparece en el panel, la navegacion compartida y `/student/avatar`.
- **R09F01T02P01** Verificar persistencia al volver a entrar, correspondencia entre personaje e imagen completa, ausencia de intercambio entre personajes, foco visible, teclado, ancho movil y `prefers-reduced-motion`. Estado: superada para el MVP estatico; las pruebas impiden equipar otro personaje y la vista fue revisada a 1440 x 1000 y 390 x 844, con ancho de documento igual al viewport. No se han anadido animaciones que deban reducirse.

- **R09F02** Conceder experiencia, niveles y monedas al completar actividades.
- **R09F02T01** Anadir a cada nodo de borrador una recompensa de monedas configurable entre 0 y 3, con una suma maxima de 20 por mision, y fijar una experiencia no configurable de 10 por actividad. Al asignar, copiar esos valores a una instantanea inmutable por asignacion y nodo. Estado: implementada; las asignaciones anteriores se migraron con 10 XP y 0 monedas por nodo, y el progreso historico recibio una unica concesion de 10 XP por alumno-nodo sin monedas retroactivas.
- **R09F02T01P01** Rechazar valores manipulados o fuera de limite, impedir publicar recompensas invalidas y comprobar que cambiar valores de un nuevo borrador o los limites globales no altera asignaciones ya creadas. Estado: superada en la suite focalizada R09F02; incluye limites por nodo y mision, instantanea y conservacion del valor comprometido.
- **R09F02T02** Integrar la concesion transaccional con la primera finalizacion valida del nodo. Los 10 puntos educativos actuales siguen perteneciendo a la inscripcion; la experiencia global no se gasta y el nivel se deriva de ella; las monedas forman un saldo gastable independiente. Estado: implementada sobre el servicio unico de progreso de R05, con concesion unica alumno-nodo y libro mayor de monedas.
- **R09F02T02P01** Probar explicacion, video, flashcards y primer quiz aprobado; suspenso, reintento, doble envio, concurrencia y una segunda inscripcion de la misma mision. Cada alumno y nodo puede recibir experiencia y monedas una sola vez, aunque conserve el progreso academico de cada inscripcion. Estado: superada; `8 passed`, `76 assertions` cubren los cuatro tipos, suspenso y reintentos, doble peticion secuencial, segunda asignacion, limites, instantanea, baja/reincorporacion y calculo de nivel. Ademas, el 2026-10-06 se ejecutaron dos `POST` HTTP simultaneos contra MySQL aislado para el mismo alumno y nodo: ambas respuestas fueron 302, y persistieron una sola fila de progreso, una sola concesion de 10 XP y un solo abono de 2 monedas.

- **R09F03** Comprar, poseer y equipar articulos exclusivamente esteticos.
- **R09F03T01** Crear el catalogo MVP de seis apariencias completas: una inicial incluida por personaje, una fantasia arcana comprable desde nivel 2 y una exploracion espacial comprable desde nivel 3. Crear propiedad y libro mayor inmutable; comprar bajo transaccion y bloqueo, con personaje, nivel, precio y saldo validados en servidor y unicidad alumno-apariencia. Alcanzar nivel no regala, compra ni equipa una skin. Estado: implementada; seeder idempotente con inicial a 0 monedas, arcana a 6 monedas desde nivel 2 y espacial a 12 monedas desde nivel 3.
- **R09F03T01P01** Probar compra valida, saldo insuficiente, articulo inactivo, precio o propietario manipulados, doble clic y dos compras concurrentes; debe existir un unico debito y una unica propiedad, sin saldo negativo. Estado: superada; la suite focalizada cubre compra valida, saldo y nivel insuficientes, articulo inactivo, campos manipulados y repeticion secuencial con una sola propiedad y debito. El 2026-10-06 se ejecutaron carreras HTTP simultaneas en MySQL aislado: dos compras del mismo articulo dejaron una sola propiedad y un solo debito de -6, y dos compras de articulos distintos con saldo para solo una dejaron una propiedad, un debito de -12 y saldo final 1.
- **R09F03T02** Crear tienda, inventario y equipamiento de una apariencia completa, verificando propiedad y correspondencia con `character_key` en servidor. Comprar y equipar son acciones separadas. Ninguna skin modifica preguntas, notas, puntos educativos, experiencia, nivel, progreso o desbloqueos. Estado: implementada en `/student/avatar`; la apariencia inicial se entrega y equipa al elegir personaje, mientras comprar no equipa automaticamente.
- **R09F03T02P01** Probar equipamiento propio y rechazo de apariencia ajena o de otro personaje; comprobar que desbloquear nivel, comprar y equipar son estados distintos, que no modifican datos academicos, que bajas y reincorporaciones conservan perfil, saldo e inventario y que otro usuario no puede consultar el perfil privado. Estado: superada en pruebas automatizadas para propiedad, personaje, rol, privacidad, separacion academica y conservacion de inventario; vista revisada en escritorio y movil sin desbordamiento.
