# Plan de hitos

Las fechas quedan pendientes hasta que el centro comunique calendario, entregas oficiales, horas del módulo y normas de evaluación.

| Hito | Fecha prevista | Objetivo | Evidencia esperada |
|---|---|---|---|
| Propuesta | Pendiente | Alcance, tecnologías, RFTP, navegación y planificación inicial. | Documentación de propuesta, README, estado del proyecto y repositorio Git local. |
| Entrega del 50 % | Pendiente | Flujo manual básico: acceso, roles, clases, misión manual con cuatro tipos, asignación y recorrido del alumno. | Demo funcional, pruebas básicas, capturas, documentación actualizada y tag cuando corresponda. |
| Entrega del 80 % | Pendiente | IA real con revisión, seguimiento docente, permisos completos y diseño adaptable. | Demo ampliada, pruebas críticas, evidencias y registro de desviaciones. |
| Entrega final | Pendiente | Correcciones, despliegue reproducible, memoria final y defensa. | Release final, manuales, resultados de pruebas, horas reales y evidencias cerradas. |

## Situacion del 80 % y ampliacion R09

- Del alcance previsto para el 80 % ya estan implementados y verificados el seguimiento docente R06, la generacion asistida R07 con una llamada real, los permisos asociados y la interfaz adaptable documentada. Esto describe estado tecnico; no afirma que el centro haya recibido o aprobado la entrega del 80 %.
- R09 fue autorizado por David como ampliacion posterior al plan maestro. R09F01T01 ya esta implementada con dos personajes y su apariencia inicial, y el nucleo de recompensas y niveles de R09F02 esta implementado. El plan original situaba tienda y economia virtual fuera del MVP, por lo que R09 no modifica ni da por incompletos R01-R08.
- La estimacion original de 200 horas no incluia R09. No se asignan horas ni fechas a la ampliacion hasta conocer el tiempo real disponible y el criterio del centro.
- El detalle funcional y tecnico se mantiene en [rftp.md](rftp.md) y [decisiones.md](decisiones.md).

## Entregas pequenas de R09

| Entrega | Alcance | Evidencia para considerarla terminada | Ubicacion propuesta |
|---|---|---|---|
| R09-A: perfil base | R09F01T01: modelo, dos personajes, apariencia inicial completa y desvio de primer acceso despues del cambio de contrasena. | Completada tecnicamente: migracion MySQL, Policy, validacion cerrada, pruebas de rol/propiedad/registro y recorrido adaptable con teclado. No equivale a una entrega academica. | Implementada durante el hito del 80 % autorizado. |
| R09-B: presencia del avatar | R09F01T02: mostrar el personaje elegido en el area del alumno y preparar apariencias completas compatibles, sin compositor por capas. | Pruebas de persistencia, correspondencia personaje-imagen, movil, teclado, foco y movimiento reducido; manifiesto de derechos. | Antes de la entrega final solo si R08 y la estabilidad del MVP no se retrasan. |
| R09-C: recompensas | R09F02T01-T02: configuracion limitada, instantaneas, XP, niveles, libro mayor y concesion idempotente. | Implementada tecnicamente y probada con cuatro actividades, quiz, doble envio secuencial, segunda inscripcion y baja/reincorporacion. Falta una carrera HTTP simultanea especifica para cerrar toda la evidencia prevista. | Implementada durante el hito del 80 % autorizado; no equivale a una entrega academica. |
| R09-D: tienda cosmetica | R09F03T01-T02: catalogo de seis imagenes completas; inicial incluida, arcana comprable desde nivel 2 y espacial desde nivel 3; compra y equipamiento separados. | Pruebas transaccionales, nivel minimo, saldo, propiedad, compatibilidad con personaje, privacidad y ausencia de efectos academicos; recorrido adaptable. | Solo si las anteriores estan cerradas; candidata principal a ampliacion posterior. |

No se iniciara una entrega de R09 sin autorizacion expresa. Si el calendario se estrecha, se conserva completo el MVP actual, se pospone primero la tienda R09-D y cualquier bloque de R09 que no este cerrado pasa a ampliacion posterior.

## Estimación inicial

Estimación total del plan maestro: 200 horas. Es una hipótesis de planificación, no horas realizadas.

| Fase | Horas de referencia acumuladas |
|---|---:|
| Propuesta | 20 |
| Entrega del 50 % | 100 |
| Entrega del 80 % | 160 |
| Entrega final | 200 |

No se registrarán horas, pruebas, capturas ni entregas futuras hasta que ocurran realmente.
