# Evidencia de concurrencia HTTP R09

Fecha de comprobacion: 2026-10-06.

## Alcance

Se revisaron las operaciones concurrentes de R09 sobre una base MySQL aislada, sin usar la base habitual ni el usuario `alumno_demo`.

- Proyecto Docker Compose: `eduquest_r09_concurrency`.
- App aislada: `http://localhost:18090`.
- MySQL aislado: puerto local `13390`, volumen `eduquest_r09_concurrency_sail-mysql`.
- Datos de prueba: usuarios `r09_*_20261006093418`, creados solo en la base aislada.
- Catalogo real: `character-a-arcana` cuesta 6 monedas desde nivel 2; `character-a-espacial` cuesta 12 monedas desde nivel 3.

La prueba no valida infraestructura de produccion ni copias programadas. Las peticiones se hicieron contra el servidor local de Sail y se bloquearon filas de MySQL de forma controlada para demostrar solapamiento.

## Controles del codigo revisados

- Finalizacion de nodo: `NodeProgressService` ejecuta una transaccion, bloquea la inscripcion y el nodo con `lockForUpdate()`, busca progreso existente y solo crea una fila nueva si no existia.
- Recompensa: `StudentRewardService` bloquea el usuario, comprueba la pareja unica alumno-nodo en `student_reward_grants`, lee la instantanea `mission_assignment_node_rewards` y registra el abono de monedas una sola vez.
- Compra cosmetica: `AvatarShopService` ejecuta una transaccion con reintentos, bloquea usuario, perfil y articulo, calcula saldo desde `coin_ledger_entries` y crea propiedad y debito juntos.
- Restricciones persistidas: `node_progress` es unico por inscripcion-nodo, `student_reward_grants` por alumno-nodo, `student_cosmetic_items` por alumno-articulo y el debito de compra por alumno-articulo-motivo.

## Metodo de solapamiento

En cada escenario se abrio una transaccion MySQL externa que bloqueaba la fila critica durante 8 segundos:

- Finalizacion: `mission_enrollments.id = 36`.
- Compras: `users.id = 10` o `users.id = 11`.

Durante ese bloqueo se lanzaron dos `POST` HTTP en jobs paralelos. La salida del candado registra adquisicion y liberacion; las dos peticiones empezaron despues de adquirir el bloqueo y terminaron despues de liberarlo.

| Escenario                                | Fila bloqueada              | Bloqueo retenido          | POST simultaneos            | Respuestas |
| ---------------------------------------- | --------------------------- | ------------------------- | --------------------------- | ---------- |
| Dos finalizaciones del mismo nodo        | `mission_enrollments.id=36` | 18:38:48.270-18:38:56.271 | 18:38:50.007 y 18:38:50.065 | 302 y 302  |
| Dos compras del mismo articulo           | `users.id=10`               | 18:38:57.179-18:39:05.180 | 18:38:59.011 y 18:38:59.101 | 302 y 302  |
| Dos compras distintas con saldo para una | `users.id=11`               | 18:39:06.086-18:39:14.087 | 18:39:07.906 y 18:39:07.987 | 302 y 302  |

Los codigos 302 son las redirecciones normales de los controladores Inertia tras una operacion aceptada o gestionada.

## Resultado persistido

| Escenario                                                     | Verificacion persistida                                                                                                                                                                                                                           |
| ------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Dos finalizaciones validas del mismo nodo por el mismo alumno | `node_progress`: 1 fila y 10 puntos. `student_reward_grants`: 1 fila, 10 XP y 2 monedas. `coin_ledger_entries`: 1 abono de 2 monedas.                                                                                                             |
| Dos compras del mismo articulo                                | `student_cosmetic_items`: 1 propiedad de `character-a-arcana`. `coin_ledger_entries`: 1 debito de -6. Saldo final: 39 monedas.                                                                                                                    |
| Dos compras de articulos distintos con saldo para una         | Antes de la carrera se dejo el saldo aislado en 13 monedas mediante el asiento `r09_concurrency_balance_adjustment`. Resultado: 1 propiedad, `character-a-espacial`; 1 debito de -12; saldo final 1. No hubo saldo negativo ni segunda propiedad. |

No se reprodujo ningun defecto en las operaciones revisadas. Por tanto no se modifico codigo funcional.

## Limitaciones

- La prueba es local y aislada; no acredita comportamiento bajo balanceadores, replicas, colas ni despliegue definitivo.
- El ajuste de saldo del tercer escenario se hizo solo en la base aislada para forzar una condicion de carrera concreta con 13 monedas disponibles.
- Las contrasenas usadas para los usuarios temporales no se documentan ni se versionan.
