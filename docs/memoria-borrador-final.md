# Memoria borrador final

Fecha de revision documental: 2026-10-07.

Este archivo es un borrador para preparar el Word final. No sustituye la plantilla academica del centro, no declara la entrega aprobada y no inventa horas, capturas, despliegue ni validacion externa.

## 1. Resumen

EDUQuest es una aplicacion web educativa que permite al profesorado transformar repasos en misiones, asignarlas a clases y revisar el avance del alumnado. El alumno recorre un mapa secuencial, completa actividades de explicacion, video, cuestionario y flashcards, y recibe puntos academicos por inscripcion. Como ampliacion posterior al alcance inicial, el proyecto incorpora mapas ilustrados por mision y un MVP cosmetico con avatar, XP, monedas y tienda.

El alcance inicial queda trazado en [RFTP](rftp.md) como R01-R08. R09 y los tres mapas ilustrados se documentan como ampliaciones posteriores. El estado de cada requisito esta resumido en [estado-hito-final.md](estado-hito-final.md).

## 2. Objetivos

Objetivo principal: construir una aplicacion reproducible para que un docente gestione clases, prepare misiones, las asigne y consulte el progreso, manteniendo permisos por rol y separacion de datos.

Objetivos especificos comprobados:

- Acceso por usuario, registro publico cerrado y separacion de paneles.
- Clases, alumnos, altas, bajas y reincorporaciones con historial.
- Misiones manuales con borrador, nodos, publicacion, duplicado, archivo y asignacion.
- Recorrido del alumno con desbloqueo secuencial, persistencia y cuestionarios corregidos en servidor.
- Seguimiento docente por clase, mision e inscripcion.
- Generacion asistida por IA como borrador revisable, sin publicacion automatica.
- Instalacion local con Docker Compose, pruebas automatizadas y datos de demo.
- Ampliacion R09: avatar, XP, monedas, catalogo cosmetico y equipamiento sin alterar evaluacion.

## 3. Arquitectura

La base tecnica usa Laravel 13, PHP 8.4 en Sail, MySQL 8.4, Vue 3, TypeScript, Inertia, Tailwind CSS, shadcn-vue, Pest, PHPStan y Pint. El frontend se comunica mediante paginas Inertia y formularios protegidos por sesion y CSRF.

Las reglas de dominio se concentran en Policies, Form Requests y servicios como `MissionLifecycle`, `MissionAssignmentManager`, `StudentMissionAccess`, `StudentProgressService`, `StudentRewardService` y `MissionDraftGenerator`. Las misiones guardan el escenario visual en `missions.map_theme`, validado por el enum `MissionMapTheme` con los valores `fantasy`, `science` y `old_west`.

Los diagramas actualizados estan en [diagramas-final.md](diagramas-final.md).

Comprobacion documental realizada el 2026-10-07: los enlaces desde esta memoria hacia documentos locales existen y `npm run check` paso con 109 archivos formateados y 77 archivos sin avisos de lint. No se pudo renderizar Mermaid en esta sesion porque no hay `mmdc` ni paquete `mermaid` instalado localmente o dentro del contenedor; la verificacion grafica de los diagramas queda pendiente o debe hacerse con el visor Markdown que use David para el Word final.

## 4. Implementacion

R01 y R02 cubren autenticacion, roles, docente, alumno, administrador, clases y matriculas. El registro publico esta desactivado y las altas controladas usan validaciones de servidor. Las bajas conservan datos y bloquean acceso sin borrar historial.

R03 permite crear borradores manuales, elegir escenario de mapa, editar nodos, publicar solo si el contenido esta preparado, duplicar misiones publicadas como borradores independientes y asignar a clases propias. Las misiones publicadas bloquean edicion de contenido.

R04 y R05 implementan el recorrido del alumno. El mapa muestra nodos disponibles, bloqueados o completados; los nodos bloqueados no exponen contenido en servidor. Las actividades de explicacion, video y flashcards se completan con confirmacion explicita; los cuestionarios guardan intentos, respuestas y mejor nota. Un nodo solo concede progreso una vez.

R06 ofrece seguimiento docente de progreso, puntos, intentos y mejor nota. Los datos se leen de inscripciones, finalizaciones e intentos persistidos, sin modificar el estado del alumno.

R07 genera misiones con ayuda de IA como borradores revisables. Las pruebas automatizadas usan proveedor HTTP simulado; la aplicacion no debe hacer llamadas reales durante la preparacion documental. Una llamada real controlada anterior quedo registrada el 2026-09-29.

R08 documenta instalacion local, pruebas, demo y evidencias. El despliegue local esta verificado; alojamiento definitivo, dominio, SSL y copias programadas siguen pendientes.

R09 es ampliacion posterior: el alumno elige personaje, recibe apariencia inicial, gana XP y monedas por completar nodos validamente por primera vez, compra cosmeticos si cumple nivel y saldo, y equipa apariencias propias. Estas acciones no modifican notas, puntos academicos, progreso ni desbloqueos.

## 5. Datos de demostracion

El comando `eduquest:prepare-demo` prepara datos locales para `carlinchis` y `alumno_demo` sin publicar contrasenas. Reutiliza servicios normales y conserva contrasena, avatar, progreso, XP, monedas y compras existentes.

La demo actual contiene cinco misiones y 20 actividades. Cubre los tres escenarios ilustrados versionados:

| Escenario       | Imagen versionada                    | Misiones demo                                                      |
| --------------- | ------------------------------------ | ------------------------------------------------------------------ |
| Fantasia        | `public/brand/maps/fantasy_map.png`  | `Guardianes del ciclo del agua`, `Laboratorio de la materia`       |
| Ciencia ficcion | `public/brand/maps/science_map.png`  | `Exploradores del sistema solar`, `Viaje al interior de la Tierra` |
| Oeste           | `public/brand/maps/old_west_map.png` | `Detectives de los ecosistemas`                                    |

## 6. Pruebas y calidad

Comprobaciones automatizadas registradas el 2026-10-07:

| Comprobacion                       | Resultado documentado                                             |
| ---------------------------------- | ----------------------------------------------------------------- |
| Revision final focalizada          | `67 passed`, `840 assertions`                                     |
| Cadena general `composer ci:check` | Frontend check, `vue-tsc`, Pint, PHPStan y Pest correctos         |
| Pest en cadena general             | `144 passed`, `1249 assertions`, `9 skipped`                      |
| Build produccion                   | `3407 modules transformed`                                        |
| Documentacion final                | `npm run check`: 109 archivos formateados y 77 sin avisos de lint |

La revision final de recorridos esta documentada en [revision-final-recorridos.md](revision-final-recorridos.md). Se contrastaron acceso, permisos, borradores, asignacion, IA como borrador, recorrido de alumno, seguimiento docente, recompensas y tienda. Tambien se corrigieron textos visibles mezclados en ingles/espanol y la preparacion de demo para cubrir los tres mapas.

## 7. Comprobacion visual

Comprobado automaticamente o por codigo:

- `missions.map_theme` existe, se valida y se expone al mapa del alumno.
- Las imagenes `fantasy_map.png`, `science_map.png` y `old_west_map.png` estan versionadas.
- El recorrido secuencial y los estados de nodo se mantienen en servidor.
- La demo asigna misiones a los tres escenarios sin fabricar progreso ni recompensas.

Pendiente de revision visual autenticada:

- Fantasia, ciencia ficcion y oeste en escritorio.
- Fantasia, ciencia ficcion y oeste en ancho movil cercano a 390 px.
- Navegacion por teclado, foco visible y recarga estable en cada mapa.
- Capturas finales de docente, alumno, seguimiento, IA, tienda y actividades.

Estas comprobaciones no se declaran superadas porque en la revision final el navegador integrado no pudo inicializarse y Chrome headless fallo antes de renderizar.

## 8. Resultados

El repositorio contiene una aplicacion funcional en local, con pruebas automatizadas y datos reproducibles de demo. La separacion entre evaluacion academica y recompensas cosmeticas esta mantenida: los puntos academicos pertenecen a inscripciones, mientras XP y monedas pertenecen al alumno y se calculan desde registros persistidos.

La IA se mantiene como ayuda para borradores revisables y no publica ni asigna contenido por si sola. El docente conserva la responsabilidad de revisar textos, completar videos y publicar manualmente.

## 9. Trabajo pendiente

- Tomar y seleccionar capturas finales autenticas, separadas de las evidencias del hito 80.
- Completar `docs/registro-horas.md` con horas reales de David y formato exigido por el centro.
- Convertir este borrador al Word final siguiendo plantilla, portada, indices, anexos y normas academicas.
- Revisar visualmente los tres mapas en localhost en escritorio y movil.
- Definir alojamiento definitivo, dominio, SSL, copias programadas y procedimiento de restauracion en produccion.
- Confirmar politica de uso de IA y cualquier declaracion academica exigida.
- Decidir si los avatares adicionales no versionados entran en una ampliacion futura o quedan fuera.

## 10. Referencias internas

- [Plan maestro](../EduQuest_Plan_Maestro_DAW.md)
- [RFTP](rftp.md)
- [Plan de hitos](plan-hitos.md)
- [Estado del proyecto](estado-proyecto.md)
- [Diagramas finales](diagramas-final.md)
- [Revision final de recorridos](revision-final-recorridos.md)
- [Guia demo final](guia-demo-final.md)
- [Capturas finales](capturas-final.md)
