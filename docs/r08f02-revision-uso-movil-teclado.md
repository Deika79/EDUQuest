# R08F02T01P01 - Revision de uso movil y teclado

> Registro tecnico de cierre. No acredita entrega academica ni sustituye las capturas autenticadas del hito 80. Revision: 2026-10-06.

## Entorno y datos usados

- Codigo revisado: rama `main` local despues del commit de instalacion/restauracion R08F02.
- Entorno probado sin tocar la demo habitual: Docker Compose `eduquest_r08_clean`.
- URL base aislada: `http://localhost:18080`.
- Base aislada: MySQL del proyecto Compose `eduquest_r08_clean`, puerto local `13306`.
- Volumen de demo habitual no usado: `eduquest_sail-mysql`.
- Usuarios de prueba creados solo en la base aislada:
    - `r08_flow_student`: alumno sin progreso inicial para revisar mapa y actividades.
    - `r08_shop_student`: alumno con 10 nodos completados por servicios de dominio para alcanzar nivel 2, 100 XP y 22 monedas, y poder probar compra/equipamiento sin gastar datos de demo.
- No se modifico el progreso de `alumno_demo` de la base habitual.

## Limitacion del navegador

El navegador integrado de Codex no estuvo disponible en esta sesion: la herramienta de control devolvio un error de entorno antes de abrir la pagina. Chrome/Edge locales tampoco expusieron CDP de forma estable. Se obtuvo una captura headless fiable solo de `/login` en 390 x 844, guardada como evidencia separada:

```text
docs/evidencias/r08f02-uso/login-mobile-overflow-before.png
```

Por esta limitacion no se presentan como realizadas las capturas autenticadas de clase, misiones, seguimiento, mapa, actividades ni tienda. Esas capturas siguen separadas de `docs/evidencias/hito-80/` y deben repetirse manualmente si se quieren adjuntar a la memoria.

## Rutas revisadas

| Ruta                                                            | Usuario                                 |  Anchuras previstas | Resultado                                                                                                                                                                  |
| --------------------------------------------------------------- | --------------------------------------- | ------------------: | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `/login`                                                        | Sin sesion                              |           390 x 844 | Captura obtenida. Se detecto desbordamiento horizontal del formulario por textos no envolvibles. Corregido en `PasskeyVerify` y `Login`.                                   |
| `/teacher/classes/1`                                            | `carlinchis` aislado                    | escritorio y 390 px | Revisada por codigo: formularios con labels, campos `w-full/min-w-0`, acciones en columna en movil. Pendiente captura autenticada manual.                                  |
| `/teacher/missions`                                             | `carlinchis` aislado                    | escritorio y 390 px | Revisada por codigo. Se anadio foco visible al textarea de descripcion manual. Pendiente captura autenticada manual.                                                       |
| `/teacher/missions/1`                                           | `carlinchis` aislado                    | escritorio y 390 px | Revisada por codigo y por pruebas de dominio existentes; sin cambio visual en esta tarea. Pendiente captura autenticada manual.                                            |
| `/teacher/tracking`                                             | `carlinchis` aislado                    | escritorio y 390 px | Revisada por codigo: selects con labels y accion principal. Pendiente captura autenticada manual.                                                                          |
| `/teacher/tracking/classes/1/assignments/{id}`                  | `carlinchis` aislado                    | escritorio y 390 px | Corregido ancho minimo de tabla a `min-w-[64rem]` dentro de `overflow-x-auto` para que el scroll horizontal sea fiable en movil.                                           |
| `/teacher/tracking/classes/1/assignments/{id}/enrollments/{id}` | `carlinchis` aislado                    | escritorio y 390 px | Corregido ancho minimo de tabla de intentos a `min-w-[40rem]` dentro de `overflow-x-auto`.                                                                                 |
| `/teacher/missions/generate`                                    | `carlinchis` aislado                    | escritorio y 390 px | Revisada por codigo: labels asociados, estados `role=status` cuando falta proveedor o hay generacion en curso. Pendiente captura autenticada manual.                       |
| `/student/missions`                                             | `r08_flow_student` / `r08_shop_student` | escritorio y 390 px | Revisada por codigo: tarjetas, avatar y metricas en grid adaptable. Pendiente captura autenticada manual.                                                                  |
| `/student/missions/6`                                           | `r08_flow_student`                      | escritorio y 390 px | Revisada por codigo: mapa lineal, botones `Open/Review`, nodos bloqueados sin enlace. Pendiente captura autenticada manual.                                                |
| `/student/missions/6/nodes/1`                                   | `r08_flow_student`                      | escritorio y 390 px | Explicacion: checkbox de confirmacion ahora tiene foco visible.                                                                                                            |
| `/student/missions/6/nodes/2`                                   | `r08_flow_student`                      | escritorio y 390 px | Video: iframe responsive `aspect-video`, enlace externo con boton. Pendiente captura autenticada manual.                                                                   |
| `/student/missions/6/nodes/3`                                   | `r08_flow_student`                      | escritorio y 390 px | Quiz: radios dentro de labels. Se anadio `focus-within:ring` al bloque de opcion y foco visible al input.                                                                  |
| `/student/missions/6/nodes/4`                                   | `r08_flow_student`                      | escritorio y 390 px | Flashcards: botones nativos con foco visible y revelado por Enter/Espacio. Pendiente captura autenticada manual.                                                           |
| `/student/avatar`                                               | `r08_shop_student`                      | escritorio y 390 px | Revisada por codigo y pruebas de tienda: imagenes `object-contain`, catalogo en grid adaptable y acciones separadas comprar/equipar. Pendiente captura autenticada manual. |

## Teclado y foco

Comprobaciones completadas por revision de componentes, pruebas de dominio y correcciones aplicadas:

- Login: campos con `Label for`, `Input` con foco visible del sistema, enlace de recuperacion envolvible y boton de llave sin forzar ancho horizontal.
- Mapa: enlaces/botones nativos para abrir nodos; los bloqueados no exponen accion.
- Explicacion/video: checkbox de confirmacion con foco visible; boton `Complete stage` solo se habilita tras confirmar.
- Quiz: cada opcion es un radio dentro de label; se refuerza el foco visible en toda la tarjeta con `focus-within`.
- Flashcards: cada tarjeta es `button`, por lo que Enter/Espacio revelan la tarjeta; el boton final queda deshabilitado hasta revisar todas.
- Seguimiento: tablas anchas quedan en contenedor `overflow-x-auto` con anchos minimos explicitos.
- Tienda: comprar y equipar son botones separados; las pruebas automatizadas verifican que no alteran progreso academico.

Queda pendiente una pasada manual real con Tab, Shift+Tab, Enter y Espacio en navegador grafico para las pantallas autenticadas, porque el navegador automatizado de esta sesion no pudo mantener una sesion interactiva.

## Problemas encontrados y corregidos

1. `/login` en 390 x 844: el boton de llave de acceso y el enlace de recuperacion no podian envolver texto; el documento crecia horizontalmente y el formulario quedaba cortado. Se corrigio permitiendo `whitespace-normal` en `PasskeyVerify` y envoltura en la fila de contrasena de `Login`.
2. Cabecera autenticada: las migas podian envolver en la cabecera movil y competir con el boton de menu. Se ocultaron en movil y se limitaron/truncaron en escritorio con contenedores `min-w-0`.
3. Quiz y confirmaciones: el foco de teclado podia quedar visible solo en el radio/checkbox pequeno. Se reforzo el foco en la tarjeta de opcion y en el checkbox de confirmacion.
4. Seguimiento docente: las tablas usaban clases `min-w-5xl` y `min-w-xl`, poco fiables para Tailwind. Se sustituyeron por `min-w-[64rem]` y `min-w-[40rem]`.
5. Formulario de mision manual: el textarea de descripcion no tenia clase de foco visible equivalente al resto de campos. Se anadio `focus-visible:ring`.

## Comprobaciones ejecutadas

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test php artisan test tests/Feature/Student/StudentMissionJourneyTest.php tests/Feature/Student/StudentQuizAttemptTest.php tests/Feature/Teacher/TeacherTrackingTest.php tests/Feature/Student/AvatarShopTest.php
```

Resultado: `33 passed`, `395 assertions`.

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test npm run types:check
```

Resultado: correcto, sin errores de TypeScript.

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test npm run check
```

Resultado: correcto, `102 files` formateados y `77 files` sin avisos de lint. Antes de ignorar `tmp/`, este comando fallo porque intento analizar perfiles temporales de Chrome, no por codigo de EDUQuest.

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test npm run build
```

Resultado: correcto. Vite genero los assets de produccion.

```powershell
docker compose -p eduquest_r08_clean --env-file .env.example exec -T laravel.test composer types:check
```

Resultado: correcto, PHPStan sin errores en `153` archivos.

```powershell
curl.exe -I http://localhost:18080/login
```

Resultado: HTTP 200 tras el build.

## Instrucciones manuales pendientes

Para cerrar la evidencia visual autenticada, repetir en navegador grafico:

1. Abrir `http://localhost:18080/login` o la base local deseada.
2. Usar una cuenta de prueba aislada, no `alumno_demo`, para completar nodos, comprar o equipar.
3. Probar escritorio y 390 x 844.
4. En cada ruta autenticada indicada arriba, comprobar `document.documentElement.scrollWidth <= window.innerWidth` salvo tablas con scroll horizontal intencionado.
5. Recorrer con Tab y Shift+Tab; activar con Enter/Espacio login, menu, mapa, flashcards, quiz, confirmaciones, compra y equipamiento.
6. Guardar capturas separadas de las del hito 80, por ejemplo en `docs/evidencias/r08f02-uso/`, sin contrasenas ni gestores de contrasenas visibles.
