# Estado de revision del hito del 80 %

> Documento de auditoria tecnica. No acredita entrega ni aprobacion del centro. Fecha de revision: 2026-10-05.

## Alcance revisado

El hito del 80 % se revisa sobre `origin/main` con la demo de nivel 3 publicada en la rama principal. El objetivo de esta tarea es documentar y preparar evidencias; no se han anadido funcionalidades nuevas.

R09 se trata aparte porque fue una ampliacion posterior al plan maestro. El plan inicial situaba tienda y economia virtual fuera del MVP; por tanto, R09 no cambia el estado de R01-R08.

## Requisitos implementados

| RFTP | Estado | Evidencia contrastada |
|---|---|---|
| R01 | Implementado para acceso, roles, cuenta activa, cambio obligatorio y Policies de los recursos actuales. | Rutas protegidas por middleware de rol, `UserPolicy`, pruebas de autenticacion y acceso por rol. |
| R02 | Implementado. | Clases, alumnos, matriculas activas/inactivas y sincronizacion con inscripciones abiertas en controladores, Policies, servicios y pruebas. |
| R03 | Implementado. | Editor manual, cuatro tipos de nodo, validacion de preparacion, publicacion inmutable, duplicacion, archivo y asignacion a clases propias. |
| R04 | Implementado. | Actividades de explicacion, video, cuestionario y flashcards; quiz corregido en servidor; intentos y feedback persistidos. |
| R05 | Implementado. | Mapa lineal, contenido bloqueado no enviado, progreso persistente, puntos unicos y prueba registrada de concurrencia MySQL para progreso educativo. |
| R06 | Implementado. | Seguimiento docente en `/teacher/tracking`, resumen por clase/asignacion, detalle individual, filtros por propietario y pruebas focalizadas. |
| R07 | Implementado en alcance del hito. | Generacion asistida con OpenAI Responses API, salida estructurada, cuota, timeout, validacion defensiva, borrador revisable y una llamada real registrada el 2026-09-29. |

## Requisitos parcialmente implementados

| RFTP | Estado | Pendiente real |
|---|---|---|
| R08 | Parcial. | Instalacion limpia local, copia MySQL y restauracion aislada documentadas en `docs/r08-instalacion-copia-restauracion.md`. Faltan horas reales, cierre de memoria final, capturas finales, validacion academica y despliegue/copia de produccion. |
| R09 | Parcial como ampliacion posterior. | R09F01, R09F02 y R09F03 MVP existen, pero siguen pendientes carreras HTTP simultaneas especificas para recompensas/monedas y compras. Los avatares adicionales recibidos quedan fuera de seguimiento. |

## Requisitos pendientes o fuera de alcance actual

- No hay despliegue definitivo verificado con HTTPS ni proveedor de alojamiento elegido. `docs/diagramas-hito-80.md` distingue el despliegue local comprobado del despliegue definitivo pendiente.
- No hay registro real de horas totalizado ni Gantt real con fechas academicas.
- La copia/restauracion de base de datos esta ejecutada solo en local aislado; no hay despliegue definitivo ni politica de copias de produccion.
- No se ha registrado aprobacion del centro, feedback oficial ni entrega academica del 50 % o del 80 %.
- No se han implementado ramificaciones, multijugador, rankings, chat, familias, app movil, SCORM, marketplace, suscripciones ni tutor conversacional.
- No existe vista previa docente que genere progreso separado; el recorrido de finalizacion sigue siendo de alumno.

## Estado especifico de R09

R09 fue autorizado por David despues del alcance inicial. La implementacion actual permite que el alumno elija un personaje, conserve una apariencia equipada, gane XP y monedas al completar actividades y compre/equipe apariencias completas en una tienda cosmetica. Esta ampliacion es estetica: no modifica notas, puntos educativos, progreso ni desbloqueos.

R09 debe presentarse como mejora adicional, no como requisito original del MVP. Las capturas y la memoria deben senalar que quedan fuera de este hito los avatares adicionales sin seguimiento, las capas intercambiables, mas colecciones y las pruebas de carrera HTTP especificas pendientes.

## Verificaciones ejecutadas en esta revision

- `docker compose exec -T laravel.test composer ci:check`: correcto. Formato/lint frontend, `vue-tsc`, Pint y PHPStan pasaron; Pest termino con `143 passed`, `1217 assertions` y `9 skipped` por verificacion de email desactivada.
- `docker compose exec -T laravel.test php artisan eduquest:prepare-demo`: correcto. Resultado: docente `carlinchis`, clase `Clase demo EDUQuest`, alumno `alumno_demo`, 5 misiones, 20 actividades, 200 XP disponibles, 45 monedas disponibles, 5 asignaciones abiertas, 5 inscripciones activas, preparadas `yes`, 0 progresos, 0 XP, 0 monedas y 0 compras.
- Instalacion limpia R08F02 en proyecto Compose aislado `eduquest_r08_clean`: migraciones, `CosmeticCatalogSeeder`, docente ficticio `carlinchis`, `eduquest:prepare-demo` y HTTP 200 en `http://localhost:18080/login`.
- Copia/restauracion R08F02: dump privado `storage/app/private/r08-backups/eduquest-r08-clean.sql`, restaurado en `eduquest_r08_restore`; recuperados 2 usuarios, 1 clase, 5 misiones, 5 asignaciones, 5 inscripciones, 1 progreso, 1 recompensa, 1 movimiento de monedas, avatar e inventario. La demo habitual mantuvo los mismos conteos antes y despues.
- Diagramas Mermaid del hito 80 revisados en `docs/diagramas-hito-80.md` contra rutas, modelos, servicios, Policies y migraciones actuales. Los 9 bloques se renderizaron a SVG con Mermaid CLI 12.0.0 usando Chrome local, y los 9 enlaces desde la memoria se comprobaron contra anclajes existentes.
