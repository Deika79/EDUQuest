# Capturas para la entrega final

Fecha de inventario: 2026-10-07.

Este inventario separa evidencias historicas, capturas finales realizadas y capturas finales pendientes. No se marcan como finales las capturas no versionadas que David aun no haya decidido incorporar.

## Archivos comprobados

| Ubicacion                                                     | Estado                                   | Uso recomendado                                                                      |
| ------------------------------------------------------------- | ---------------------------------------- | ------------------------------------------------------------------------------------ |
| `docs/evidencias/hito-50/`                                    | Versionado, 5 PNG reales.                | Evidencia historica del hito 50. No sustituye capturas finales.                      |
| `docs/evidencias/r08f02-uso/login-mobile-overflow-before.png` | Versionado, 1 PNG real.                  | Evidencia de defecto movil previo. No usar como captura final positiva.              |
| `docs/evidencias/hito-80/`                                    | Existe en el arbol local, no versionado. | Mantener separado hasta decision de David; no incorporado a esta entrega documental. |
| `docs/evidencias/final/`                                      | No existe actualmente.                   | Carpeta sugerida para capturas finales cuando David las seleccione.                  |

## Capturas finales realizadas

| Nombre                           | Pantalla | Estado    |
| -------------------------------- | -------- | --------- |
| Ninguna captura final versionada | -        | Pendiente |

## Capturas finales pendientes

| Nombre sugerido                      | Pantalla                         | Perfil     | Que debe verse                                          | Formato    |
| ------------------------------------ | -------------------------------- | ---------- | ------------------------------------------------------- | ---------- |
| `final-01-login.png`                 | `/login`                         | Sin sesion | Acceso por usuario, sin registro publico.               | Escritorio |
| `final-02-docente-clase.png`         | `/teacher/classes/{id}`          | Docente    | Clase demo, alumnado y matriculas.                      | Escritorio |
| `final-03-docente-misiones.png`      | `/teacher/missions`              | Docente    | Misiones demo y escenarios.                             | Escritorio |
| `final-04-docente-selector-mapa.png` | Borrador editable                | Docente    | Selector Fantasia, Ciencia ficcion y Oeste.             | Escritorio |
| `final-05-mapa-fantasia-desktop.png` | `/student/missions/{enrollment}` | Alumno     | Fondo fantasia, nodos y conexiones.                     | Escritorio |
| `final-06-mapa-ciencia-desktop.png`  | `/student/missions/{enrollment}` | Alumno     | Fondo ciencia ficcion, nodos y conexiones.              | Escritorio |
| `final-07-mapa-oeste-desktop.png`    | `/student/missions/{enrollment}` | Alumno     | Fondo oeste, nodos y conexiones.                        | Escritorio |
| `final-08-mapa-fantasia-mobile.png`  | Misma mision fantasia            | Alumno     | Sin desbordes a ancho cercano a 390 px.                 | Movil      |
| `final-09-mapa-ciencia-mobile.png`   | Misma mision ciencia             | Alumno     | Sin desbordes a ancho cercano a 390 px.                 | Movil      |
| `final-10-mapa-oeste-mobile.png`     | Misma mision oeste               | Alumno     | Sin desbordes a ancho cercano a 390 px.                 | Movil      |
| `final-11-actividad-explicacion.png` | Actividad explicacion            | Alumno     | Texto, aviso y accion de confirmacion.                  | Escritorio |
| `final-12-actividad-video.png`       | Actividad video                  | Alumno     | Reproductor/enlace alternativo y aviso.                 | Escritorio |
| `final-13-actividad-quiz.png`        | Actividad quiz                   | Alumno     | Preguntas sin soluciones previas.                       | Escritorio |
| `final-14-actividad-flashcards.png`  | Actividad flashcards             | Alumno     | Tarjetas y revision completa.                           | Escritorio |
| `final-15-seguimiento-docente.png`   | `/teacher/tracking`              | Docente    | Progreso, puntos, intentos y mejor nota.                | Escritorio |
| `final-16-generacion-ia.png`         | `/teacher/missions/generate`     | Docente    | Formulario IA y aviso de revision.                      | Escritorio |
| `final-17-borrador-ia.png`           | Editor de mision IA              | Docente    | Estado borrador, source IA y revision humana pendiente. | Escritorio |
| `final-18-tienda-avatar.png`         | `/student/avatar`                | Alumno     | Avatar, saldo, nivel, inventario y catalogo.            | Escritorio |
| `final-19-panel-alumno-mobile.png`   | `/student/missions`              | Alumno     | Misiones, avatar y progreso sin solapes.                | Movil      |

## Reglas para tomarlas

- No capturar contrasenas, `.env`, claves de IA ni terminales con secretos.
- Usar cuentas aisladas para acciones que cambian datos.
- Antes de pulsar comprar, equipar, completar o generar, decidir si esa accion debe formar parte de la evidencia.
- Mantener separadas las carpetas `hito-50`, `hito-80` y `final`.
- No versionar capturas nuevas hasta que David decida cuales entran en la memoria.
