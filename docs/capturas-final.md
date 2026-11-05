# Capturas para la entrega final

Fecha de revisión editorial: 2026-11-05.

Este inventario distingue evidencias históricas, capturas locales del hito 80 y capturas finales incorporadas. No se incorporan capturas que muestren contraseñas, claves, terminales con secretos ni evidencias privadas.

## Comparación de capturas existentes

| Captura | Pantalla comparada | Estado | Motivo editorial |
| --- | --- | --- | --- |
| `docs/evidencias/hito-50/02-docente-clase-alumno.png` | `/teacher/classes/{id}` | debe sustituirse | La pantalla actual está en español y reorganiza formularios y matrícula. |
| `docs/evidencias/hito-50/03-docente-mision-publicada-asignada.png` | `/teacher/missions/{id}` y asignación | debe sustituirse | Pertenece al flujo anterior y no muestra escenarios/mapas actuales. |
| `docs/evidencias/hito-50/04-alumno-mapa-bloqueado.png` | `/student/missions/{enrollment}` | debe sustituirse | El mapa actual usa fondos ilustrados por tema; la captura es una lista antigua. |
| `docs/evidencias/hito-50/05-alumno-quiz-suspenso.png` | Actividad de cuestionario | debe sustituirse | Es evidencia histórica del hito 50 y no representa el estado final de UI/progreso. |
| `docs/evidencias/hito-50/06-alumno-mision-completada.png` | Misión completada | debe sustituirse | Es evidencia histórica; puede quedar como contexto, no como captura final. |
| `docs/evidencias/hito-80/alumno_avatar_upgarde.png` | `/student/avatar` | reutilizable | La tienda/avatar actual conserva estructura, recompensas y catálogo cosmético. |
| `docs/evidencias/hito-80/alumno_captura_panel.png` | `/student/missions` | reutilizable | Sigue siendo fiel para panel de alumno en escritorio; no sustituye revisión móvil. |
| `docs/evidencias/hito-80/alumno_mision_video.png` | Actividad de vídeo | reutilizable | La actividad mantiene vídeo/enlace y aviso de revisión. |
| `docs/evidencias/hito-80/alumno_mission_ explanation.png` | Actividad de explicación | reutilizable | Sigue representando el tipo explicación. |
| `docs/evidencias/hito-80/alumno_mission_completed.png` | Misión completada | reutilizable | Sigue siendo válida como evidencia de finalización. |
| `docs/evidencias/hito-80/alumno_mission_flashcards.png` | Actividad de flashcards | reutilizable | La pantalla actual mantiene tarjetas y revisión. |
| `docs/evidencias/hito-80/alumno_mission_map.png` | `/student/missions/{enrollment}` | debe sustituirse | No muestra los mapas ilustrados actuales de fantasía, ciencia ficción ni oeste. |
| `docs/evidencias/hito-80/alumno_mission_quiz.png` | Actividad de cuestionario | reutilizable | Sigue siendo fiel para el cuestionario antes de responder. |
| `docs/evidencias/hito-80/alumno_quiz_answers.png` | Resultado de cuestionario | reutilizable | Sigue siendo útil como evidencia de respuestas y feedback. |
| `docs/evidencias/hito-80/clase.png` | `/teacher/classes/{id}` | reutilizable | La pantalla actual mantiene edición de clase, alta y matrícula. |
| `docs/evidencias/hito-80/formulario_ia.png` | `/teacher/missions/generate` | reutilizable | Mantiene formulario, límites y aviso de revisión humana. |
| `docs/evidencias/hito-80/mission.png` | Edición/detalle de misión | reutilizable | Sirve para la misión docente, aunque no sustituye una captura específica del selector de mapa si se quisiera documentar. |
| `docs/evidencias/hito-80/teacher.png` | Panel docente | reutilizable | Sigue siendo válida como contexto de panel docente. |
| `docs/evidencias/hito-80/tracking.png` | `/teacher/tracking` | reutilizable | Mantiene filtros y acceso a seguimiento por clase/misión. |

## Inventario de imágenes finales

| Archivo en `docs/evidencias/final/` | Estado | Alcance comprobado visualmente |
| --- | --- | --- |
| `final-landing-escritorio.png` | reutilizable | Landing con marca, acceso y enlace al tráiler. |
| `final-mapa-fantasia-escritorio.png` | reutilizable | Vista parcial de la aplicación, mapa y actividades completadas. No incluye la cabecera ni todo el mapa. |
| `final-mapa-fantasia-movil.png` | reutilizable | Vista parcial en formato móvil, progreso 0 %, nodo disponible y bloqueados. No se ha certificado un ancho concreto. |
| `final-mapa-ciencia-ficcion-escritorio.png` | debe sustituirse | Ilustración sin interfaz ni nodos. Conservada como recurso visual; sustituida en la memoria por la captura autenticada de ciencia ficción. |
| `final-mapa-oeste-escritorio.png` | debe sustituirse | Ilustración sin interfaz ni nodos. Conservada como recurso visual; sustituida en la memoria por la captura autenticada de oeste. |
| `final-mapa-ciencia-ui-desktop.png` | reutilizable | Captura autenticada de la misión 11, escritorio a 1440 px, cuatro nodos y progreso. |
| `final-mapa-ciencia-ui-mobile.png` | reutilizable | Misma misión a 390 px en emulación móvil, cuatro nodos y lista sin desbordamiento horizontal. |
| `final-mapa-oeste-ui-desktop.png` | reutilizable | Captura autenticada de la misión 12, escritorio a 1440 px, cuatro nodos y progreso. |
| `final-mapa-oeste-ui-mobile.png` | reutilizable | Misma misión a 390 px en emulación móvil, cuatro nodos y lista sin desbordamiento horizontal. |

No se deducen fecha de captura, dispositivo, usuario ni matrícula a partir del nombre de archivo. Se han conservado las imágenes originales sin alterarlas.

## Capturas solicitadas completadas

| Captura realizada | URL | Usuario | Qué se ha comprobado |
| --- | --- | --- | --- |
| `final-mapa-ciencia-ui-desktop.png` | `/student/missions/11` | `revision_final_alumno` | Ciencia ficción, cuatro nodos y progreso al 0 %; captura completa a 1440 px de ancho. |
| `final-mapa-oeste-ui-desktop.png` | `/student/missions/12` | `revision_final_alumno` | Oeste, cuatro nodos y progreso al 0 %; captura completa a 1440 px de ancho. |
| `final-mapa-ciencia-ui-mobile.png` | `/student/missions/11` | `revision_final_alumno` | Mapa, cuatro nodos y lista de actividades a 390 px, sin desbordamiento horizontal. |
| `final-mapa-oeste-ui-mobile.png` | `/student/missions/12` | `revision_final_alumno` | Mapa, cuatro nodos y lista de actividades a 390 px, sin desbordamiento horizontal. |

Realizadas el 2026-11-05 en Chrome local sobre `http://localhost:8080`. Las misiones ya existían y estaban asignadas a la cuenta de revisión; no se creó contenido ni se llamó a IA. Las misiones habituales de `alumno_demo` permanecen sin cambios. La captura móvil es emulación de navegador, no dispositivo físico. La sesión temporal se revoca al terminar. Las cuatro imágenes están incorporadas a la memoria; las ilustraciones originales se conservan separadas. No quedan capturas pendientes de este lote.

## Reglas para tomarlas

- No capturar contraseñas, `.env`, claves de IA ni terminales con secretos.
- Usar cuentas aisladas para acciones que cambian datos.
- Antes de pulsar comprar, equipar, completar o generar, decidir si esa acción debe formar parte de la evidencia.
- Mantener separadas las carpetas `hito-50`, `hito-80` y `final`.
- David ha autorizado incorporar y versionar este lote de capturas junto con la memoria.
