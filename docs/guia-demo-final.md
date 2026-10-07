# Guia de demo final

Duracion objetivo: 10-15 minutos. No incluye contrasenas.

Preparacion previa: arrancar `http://localhost:8080/login`, confirmar que existen los datos demo y evitar repetir acciones que modifiquen progreso, compras o estado salvo que David quiera mostrar ese cambio.

## Recorrido recomendado

| Minuto | Perfil     | Pantalla                     | Accion                                                                                                               | Modifica datos              |
| ------ | ---------- | ---------------------------- | -------------------------------------------------------------------------------------------------------------------- | --------------------------- |
| 0-1    | Sin sesion | `/login`                     | Mostrar acceso por usuario y ausencia de registro publico.                                                           | No                          |
| 1-2    | Docente    | `/teacher/classes`           | Abrir clase demo y ensenar alumnado/matriculas.                                                                      | No si solo se navega        |
| 2-3    | Docente    | `/teacher/missions`          | Mostrar misiones demo y escenarios.                                                                                  | No                          |
| 3-4    | Docente    | Detalle de mision            | Abrir una mision publicada, nodos y asignaciones.                                                                    | No                          |
| 4-5    | Docente    | Nuevo/editar borrador        | Mostrar selector de mapa: Fantasia, Ciencia ficcion y Oeste. Guardar solo si se usa un borrador aislado.             | Si se guarda                |
| 5-6    | Docente    | `/teacher/missions/generate` | Mostrar IA como formulario de borrador revisable. No pulsar generar salvo con proveedor simulado o cuenta preparada. | Si se genera                |
| 6-7    | Alumno     | `/student/missions`          | Mostrar misiones, XP, nivel, monedas y avatar equipado.                                                              | No                          |
| 7-9    | Alumno     | Mapas de mision              | Abrir una mision de fantasia, una de ciencia ficcion y una de oeste; comprobar imagen, nodos y conexiones.           | No si no se completan nodos |
| 9-11   | Alumno     | Actividades                  | Mostrar explicacion, video, quiz y flashcards. Completar solo en cuenta aislada o si se acepta cambiar la demo.      | Si se confirma/completa     |
| 11-12  | Docente    | `/teacher/tracking`          | Mostrar progreso, puntos, intentos, mejor nota y detalle.                                                            | No                          |
| 12-14  | Alumno     | `/student/avatar`            | Mostrar tienda, requisitos de nivel, saldo, compra y equipamiento. Comprar/equipar solo con cuenta aislada.          | Si se compra o equipa       |
| 14-15  | Cierre     | README/docs                  | Mostrar comandos reproducibles, pruebas y pendientes visuales.                                                       | No                          |

## Tres mapas de la demo

- Fantasia: `Guardianes del ciclo del agua` o `Laboratorio de la materia`.
- Ciencia ficcion: `Exploradores del sistema solar` o `Viaje al interior de la Tierra`.
- Oeste: `Detectives de los ecosistemas`.

En cada mapa conviene revisar:

- Imagen de fondo correcta.
- Nodos dentro del area visible.
- Conexiones consecutivas.
- Primer nodo disponible o estado real segun progreso.
- Lista textual equivalente para teclado y lector de pantalla.
- Recarga estable sin recolocar el recorrido al azar.

## Acciones que conviene evitar en cuentas habituales

- Completar nodos de `alumno_demo` si se quiere mantener su progreso intacto.
- Enviar quizzes si no se desea alterar intentos.
- Comprar o equipar apariencias si se quiere conservar inventario/estado visual.
- Generar IA real con una clave externa. Para pruebas, usar los mecanismos simulados de la suite.
- Restablecer contrasenas de demo salvo decision expresa.

## Variante segura con datos aislados

Para una demo sin tocar cuentas habituales, crear o reutilizar cuentas locales aisladas y asignar misiones de prueba. La revision final uso `revision_final_docente` y `revision_final_alumno`, con tres misiones independientes: `Revision fantasia`, `Revision ciencia` y `Revision oeste`.

Si David repite la demo habitual con `eduquest:prepare-demo`, el comando conserva contrasena, avatar, progreso, XP, monedas y compras existentes; aun asi, completar actividades o comprar desde la interfaz si modifica datos.
