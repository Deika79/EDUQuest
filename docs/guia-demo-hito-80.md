# Guion de demostracion del hito del 80 %

Duracion objetivo: 10-15 minutos. URL local: `http://localhost:8080/login`.

No guardar contrasenas en Git, capturas ni documentos. El docente esperado para la demo es `carlinchis` y el alumno es `alumno_demo`; sus contrasenas deben mantenerse solo en el entorno local.

## Preparacion previa

1. Arrancar contenedores: `docker compose up -d`.
2. Ejecutar migraciones si procede: `docker compose exec -T laravel.test php artisan migrate --force`.
3. Sembrar catalogo cosmetico si procede: `docker compose exec -T laravel.test php artisan db:seed --class=CosmeticCatalogSeeder --force`.
4. Preparar datos demo sin contrasena en Git: `docker compose exec laravel.test php artisan eduquest:prepare-demo`.
5. Confirmar que existen una clase, cinco misiones, veinte nodos, cinco asignaciones abiertas y el alumno `alumno_demo`.

## Recorrido docente: `carlinchis`

1. Entrar en `http://localhost:8080/login` como docente.
2. Abrir `/teacher/classes`.
   - Mostrar `Clase demo EDUQuest`, nivel, asignatura y `alumno_demo`.
   - Acciones que modifican datos: crear/editar clase, crear alumno, matricular, dar de baja o reincorporar.
3. Abrir `/teacher/missions`.
   - Mostrar las cinco misiones publicadas de ciencias.
   - Entrar en `Exploradores del sistema solar`.
   - Mostrar nodos, tipos de actividad, estado publicado e inmutabilidad.
   - Acciones que modifican datos: crear mision, editar borrador, mover nodos, publicar, duplicar, archivar, asignar o retirar asignacion.
4. Abrir `/teacher/tracking`.
   - Seleccionar `Clase demo EDUQuest` y una mision asignada.
   - Mostrar porcentaje, puntos, intentos, mejor nota y detalle por nodo.
   - Acciones que modifican datos: ninguna; el seguimiento es lectura.
5. Abrir `/teacher/missions/generate`.
   - Mostrar formulario de generacion asistida, limites y estado de configuracion.
   - Explicar que una salida valida crea borrador propio, no publica ni asigna.
   - Acciones que modifican datos: pulsar `Generar borrador` crea una fila `ai_generations` y una mision en borrador; consume cuota/API si hay clave real. Ejecutarlo solo si se quiere crear una evidencia nueva.

## Recorrido alumno: `alumno_demo`

1. Entrar como alumno en `http://localhost:8080/login`.
2. Si el perfil de avatar no existiera, completar `/student/avatar/setup`.
   - Acciones que modifican datos: elegir personaje crea perfil, concede apariencia inicial y la equipa.
3. Abrir `/student/missions`.
   - Mostrar portada, avatar equipado, nivel, XP, monedas, misiones disponibles y progreso.
   - Acciones que modifican datos: ninguna al mirar el panel.
4. Entrar en `Exploradores del sistema solar`.
   - Mostrar mapa lineal, nodos disponibles/bloqueados/completados y alternativa textual.
   - Acciones que modifican datos: abrir un nodo no deberia modificar datos; completar un nodo si.
5. Completar una explicacion o video.
   - Mostrar que la confirmacion desbloquea el siguiente nodo.
   - Accion que modifica datos: crea `node_progress`, puntos educativos, XP y monedas si es la primera finalizacion valida de ese alumno-nodo.
6. Completar el cuestionario.
   - Para demostrar suspenso, responder alguna pregunta mal: guarda intento pero no completa nodo si no supera el 70 %.
   - Para demostrar avance, responder correctamente: guarda intento aprobado y completa nodo.
   - Accion que modifica datos: cada envio crea intento; solo el primer aprobado concede progreso/recompensa.
7. Completar flashcards.
   - Revelar todas las tarjetas y confirmar.
   - Accion que modifica datos: crea progreso y recompensa si no existian.
8. Abrir `/student/avatar`.
   - Mostrar inventario, tienda, requisitos de nivel, saldo y apariencias.
   - Acciones que modifican datos: comprar descuenta monedas y crea propiedad; equipar cambia la apariencia equipada. Comprar y equipar son acciones separadas.

## Orden recomendado para no contaminar la demo

1. Primero mostrar docente y seguimiento en lectura.
2. Despues mostrar alumno sin completar nada si se quiere conservar el estado inicial.
3. Ejecutar finalizaciones, compras o generacion IA solo al final, cuando David decida que conviene crear esa evidencia.
4. Si se necesita reiniciar contrasena del alumno: `docker compose exec laravel.test php artisan eduquest:prepare-demo --reset-student-password`. No versionar esa contrasena.

