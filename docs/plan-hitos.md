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
- R09 fue autorizado por David como ampliacion posterior al plan maestro. El plan original situaba tienda y economia virtual fuera del MVP, por lo que R09 no modifica ni da por incompletos R01-R08.
- La estimacion original de 200 horas no incluia R09. No se asignan horas ni fechas a la ampliacion hasta conocer el tiempo real disponible y el criterio del centro.
- El detalle funcional y tecnico se mantiene en [rftp.md](rftp.md) y [decisiones.md](decisiones.md).

## Entregas pequenas de R09

| Entrega | Alcance | Evidencia para considerarla terminada | Ubicacion propuesta |
|---|---|---|---|
| R09-A: perfil base | R09F01T01: modelo, opciones iniciales y desvio de primer acceso despues del cambio de contrasena. | Migraciones MySQL, Policies, pruebas de rol/propiedad y registro publico aun bloqueado. | Antes de la entrega final solo si R08 y la estabilidad del MVP no se retrasan; en otro caso, ampliacion posterior. |
| R09-B: compositor accesible | R09F01T02: capas originales iniciales, editor, persistencia y miniatura propia. | Pruebas de combinaciones, movil, teclado, foco y movimiento reducido; manifiesto de derechos. | Mismo criterio que R09-A y siempre despues de cerrar su modelo. |
| R09-C: recompensas | R09F02T01-T02: configuracion limitada, instantaneas, XP, niveles, libro mayor y concesion idempotente. | Pruebas de cuatro actividades, quiz, doble envio, concurrencia, segunda inscripcion y baja/reincorporacion. | Antes de tienda; puede ser la ultima ampliacion previa a entrega final. |
| R09-D: tienda cosmetica | R09F03T01-T02: catalogo pequeno, compra, inventario y equipamiento. | Pruebas transaccionales, saldo, propiedad, privacidad y ausencia de efectos academicos; recorrido adaptable. | Solo si las anteriores estan cerradas; candidata principal a ampliacion posterior. |

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
