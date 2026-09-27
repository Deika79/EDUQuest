# Guia de demo del hito del 50 %

Esta guia recorre solo las funciones implementadas hasta R05. No incluye IA ni seguimiento docente y no acredita la entrega academica del hito.

## Preparacion

1. Arrancar el entorno con `docker compose up -d`.
2. Aplicar migraciones con `docker compose exec -T laravel.test php artisan migrate --force`.
3. Abrir `http://localhost:8080/login`.
4. Si no existe un administrador, crearlo con `docker compose exec laravel.test php artisan eduquest:create-admin`. La contrasena se solicita de forma interactiva.
5. Usar exclusivamente nombres, correos y contenidos ficticios. No guardar contrasenas en esta guia, capturas, comandos ni Git.

## Recorrido

1. **Administrador:** iniciar sesion, crear un docente con contrasena temporal y comprobar que aparece activo.
2. **Docente:** iniciar sesion con la contrasena temporal y completar el cambio obligatorio.
3. **Clase:** abrir `/teacher/classes`, crear una clase ficticia y una cuenta de alumno con `username` unico, email opcional y contrasena temporal.
4. **Mision:** abrir `/teacher/missions`, crear un borrador manual y anadir, en este orden, una explicacion, un video valido, un cuestionario de tres preguntas y un bloque de flashcards.
5. **Distribucion:** publicar la mision, comprobar que el contenido queda en modo lectura y asignarla a la clase.
6. **Alumno:** iniciar sesion, completar el cambio obligatorio y abrir el mapa de la mision.
7. **Bloqueos:** comprobar que solo el primer nodo esta disponible y que el mapa no muestra el contenido de los nodos bloqueados.
8. **Actividades:** confirmar la explicacion y el video. En el cuestionario, responder primero 2 de 3 con umbral del 70 % y comprobar el suspenso del 66,67 %; repetir con 3 de 3 y comprobar el aprobado. Revelar todas las flashcards y confirmar el repaso.
9. **Resultado:** comprobar `4 of 4 nodes`, `40 points` y `100%`. Volver a abrir un nodo completado no debe conceder puntos adicionales.

## Cuentas locales usadas en la revision

| Rol | Username | Observacion |
| --- | --- | --- |
| Administrador | `demo50_admin` | Cuenta ficticia local. |
| Docente | `demo50_teacher` | Cuenta ficticia local creada desde administracion. |
| Alumno | `demo50_student` | Cuenta ficticia local creada desde la clase. |

Las contrasenas no se documentan. Para una nueva demo deben definirse localmente mediante el comando interactivo y los formularios protegidos de alta.

## Evidencias

El resumen verificable esta en [evidencias-hito-50.md](evidencias-hito-50.md) y las capturas se encuentran en `docs/evidencias/hito-50/`.
