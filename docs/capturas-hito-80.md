# Inventario de capturas para la memoria del 80 %

Estado: inventario preparado el 2026-10-05. No se marcan como realizadas las capturas autenticadas porque requieren credenciales locales no versionadas y algunas acciones pueden modificar datos.

## Capturas necesarias

| Nombre sugerido | Pantalla | Usuario | Que debe verse | Apartado de memoria |
|---|---|---|---|---|
| `80-01-login.png` | `/login` | Sin sesion | Acceso por usuario, sin enlace de registro publico. | Tecnologias / seguridad |
| `80-02-docente-clase-demo.png` | `/teacher/classes/{id}` | `carlinchis` | `Clase demo EDUQuest`, `alumno_demo`, matricula activa y datos ficticios. | Casos de uso CU02 |
| `80-03-docente-misiones-publicadas.png` | `/teacher/missions` | `carlinchis` | Cinco misiones demo publicadas o disponibles para entrar. | CU03 / CU05 |
| `80-04-docente-mision-detalle.png` | `/teacher/missions/{id}` | `carlinchis` | Nodos de `Exploradores del sistema solar`, asignacion abierta e inmutabilidad de publicada. | Disenos / implementacion R03 |
| `80-05-seguimiento-resumen.png` | `/teacher/tracking` o resultado de clase/asignacion | `carlinchis` | Tabla de seguimiento con porcentaje, puntos, intentos, mejor nota y estados. | CU07 / R06 |
| `80-06-seguimiento-detalle.png` | Detalle de inscripcion | `carlinchis` | Nodos, intentos de quiz y separacion entre progreso y nota. | Pruebas / seguimiento |
| `80-07-generacion-ia-formulario.png` | `/teacher/missions/generate` | `carlinchis` | Formulario, limites de nodos y mensaje de configuracion si falta API. | R07 / IA |
| `80-08-generacion-ia-borrador.png` | `/teacher/missions/{id}` generado por IA | `carlinchis` | Estado borrador, origen IA, revision humana pendiente y video pendiente. | R07 / revision humana |
| `80-09-alumno-panel.png` | `/student/missions` | `alumno_demo` | Avatar, nivel, XP, monedas, misiones y progreso. | Experiencia alumno / R09 |
| `80-10-alumno-mapa-inicial.png` | `/student/missions/{enrollment}` | `alumno_demo` | Mapa lineal con disponible/bloqueado/completado y sin contenido bloqueado. | R05 / diseno |
| `80-11-alumno-actividad-quiz.png` | Actividad quiz | `alumno_demo` | Preguntas y opciones sin respuestas correctas antes del envio. | R04 / seguridad |
| `80-12-alumno-quiz-feedback.png` | Resultado de quiz enviado | `alumno_demo` | Nota, aprobado/suspenso y feedback posterior. | R04 / pruebas |
| `80-13-alumno-flashcards.png` | Actividad flashcards | `alumno_demo` | Tarjetas revelables y boton de confirmacion tras revisar todas. | R04 |
| `80-14-alumno-mision-completada.png` | Mapa tras completar | `alumno_demo` | 100 %, nodos completados, puntos y desbloqueos. | Resultados del recorrido |
| `80-15-avatar-tienda.png` | `/student/avatar` | `alumno_demo` | Apariencia equipada, inventario, catalogo, nivel, saldo, estados de compra. | R09 |
| `80-16-avatar-equipado-cabecera.png` | `/student/missions` tras equipar | `alumno_demo` | Persistencia visual del avatar equipado en cabecera/panel. | R09 |
| `80-17-movil-alumno-panel.png` | `/student/missions` en 390 x 844 | `alumno_demo` | Sin desbordamiento horizontal ni solapes. | Diseno adaptable |
| `80-18-movil-tienda.png` | `/student/avatar` en 390 x 844 | `alumno_demo` | Catalogo legible, imagenes proporcionadas y acciones accesibles. | Diseno adaptable |

## Capturas que puede hacer David en localhost

1. Abrir la URL indicada con el usuario correspondiente.
2. Ocultar o no mostrar gestores de contrasenas.
3. No capturar `.env`, terminales con secretos ni contrasenas.
4. Para capturas de acciones destructivas o que consumen datos, hacer primero la captura antes de pulsar el boton. En particular: generacion IA, completar nodos, comprar y equipar.
5. Guardar en `docs/evidencias/hito-80/` con los nombres sugeridos.

## Capturas no realizadas por esta auditoria

- No se realizaron capturas autenticadas nuevas durante esta tarea porque no se deben pedir ni versionar contrasenas.
- No se generaron capturas de acciones pendientes como si ya estuvieran ejecutadas.
- Las capturas existentes del 50 % siguen en `docs/evidencias/hito-50/` y solo deben usarse como evidencia de aquel recorrido, no como sustituto de R06, R07 o R09.

