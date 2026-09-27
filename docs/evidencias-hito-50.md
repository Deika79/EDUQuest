# Evidencias de revision del hito del 50 %

Revision tecnica previa a la presentacion. El feedback del centro no esta marcado como entregado y no existe la etiqueta `hito-50`.

## Implementado

- Alta controlada de docente, clase y alumno con cambio obligatorio de contrasena temporal.
- Edicion manual, validacion, publicacion y asignacion de una mision con explicacion, video, cuestionario y flashcards.
- Mapa lineal con bloqueos, actividades del alumno, intentos de cuestionario, progreso y diez puntos unicos por nodo.
- Policies y consultas por propietario para clases, misiones, asignaciones e inscripciones.
- Unicidad transaccional del progreso por inscripcion y nodo.

## Probado en navegador

Se uso Chrome real contra `http://localhost:8080` y MySQL 8.4, con los datos ficticios descritos en la guia.

1. El administrador creo `demo50_teacher` con cambio de contrasena pendiente.
2. El docente cambio la contrasena temporal, creo `Aula Demo Fracciones` y creo/matriculo `demo50_student`.
3. El docente creo `Rescate Demo de las Fracciones`, guardo los cuatro tipos de nodo, publico y asigno la mision.
4. El alumno cambio su contrasena temporal. El mapa inicial mostro un nodo disponible y tres bloqueados, sin contenido de preguntas ni reversos bloqueados.
5. Explicacion y video avanzaron solo tras confirmacion. La interfaz indico que la confirmacion no acredita comprension ni visionado completo.
6. El primer intento del cuestionario obtuvo 2 de 3, `66.67%` y `Not passed`; no desbloqueo el siguiente nodo. El segundo obtuvo 3 de 3 y aprobo.
7. Tras revelar todas las tarjetas y confirmar el repaso, el mapa mostro 4 de 4 nodos, 40 puntos y 100 %.

## Seguridad comprobada

- Antes del envio del cuestionario, la respuesta Inertia no contenia `is_correct`, explicaciones ni identificadores de solucion. Tras el envio si mostro feedback.
- El mapa no envio contenido de nodos bloqueados y las rutas directas de lectura y finalizacion fueron rechazadas.
- Las peticiones con clases, misiones, nodos, preguntas u opciones ajenas fueron rechazadas.
- La seleccion focalizada de seguridad termino con `55 passed` y `462 assertions`.

## Concurrencia real

Se creo una mision ficticia aislada de un nodo y se lanzaron dos `POST` simultaneos con `Promise.all` desde la misma sesion autenticada contra el servidor local. Ambas respuestas siguieron su redireccion controlada y terminaron con HTTP `200` en `/student/missions/3`; ninguna produjo un error 500.

La consulta posterior a MySQL devolvio:

```json
{
    "responses": [
        { "status": 200, "ok": true, "redirected": true },
        { "status": 200, "ok": true, "redirected": true }
    ],
    "progress_rows": 1,
    "points_total": 10,
    "activity_started": true,
    "completed": true
}
```

Esta es una carrera simultanea real y se distingue de la prueba automatizada previa de reenvios secuenciales.

## Comprobaciones finales

- `composer ci:check`: formato y lint frontend correctos; TypeScript correcto; Pint correcto en 140 archivos; PHPStan sin errores en 114 archivos; Pest `102 passed`, `639 assertions` y 9 pruebas omitidas porque la verificacion de email de Fortify esta desactivada.
- `npm run build`: correcto, `3395 modules transformed`.
- El primer intento de `composer ci:check` encontro el perfil temporal de Chrome dentro de `tmp/`; se retiro ese temporal y se repitio la cadena completa correctamente.

## Capturas conservadas

- `docs/evidencias/hito-50/02-docente-clase-alumno.png`: clase y alta controlada de alumno.
- `docs/evidencias/hito-50/03-docente-mision-publicada-asignada.png`: mision publicada, contenido inmutable y asignacion confirmada.
- `docs/evidencias/hito-50/04-alumno-mapa-bloqueado.png`: mapa inicial con tres nodos bloqueados y cero puntos.
- `docs/evidencias/hito-50/05-alumno-quiz-suspenso.png`: intento de 2/3, 66,67 % y feedback posterior.
- `docs/evidencias/hito-50/06-alumno-mision-completada.png`: cuatro nodos completados, 40 puntos y 100 %.

No se conserva la captura administrativa porque incluia una cuenta preexistente ajena a los datos ficticios. Ninguna evidencia contiene contrasenas ni datos de menores reales.

## Corregido y pendiente

- Corregido: los avisos docentes obsoletos que afirmaban que actividades y progreso aun no existian.
- Sin bloqueo funcional reproducible tras la correccion.
- Pendiente: revision de la demo por David, feedback oficial del centro, seguimiento docente, IA, recorrido movil/teclado, instalacion limpia y restauracion de copia.
