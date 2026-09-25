# RFTP - Requisitos, funciones, tareas y pruebas

Estado general: planificación inicial. Las pruebas están planificadas, no ejecutadas.

## R01 Solo deben acceder personas autorizadas y cada una debe tener los permisos de su perfil.

- **R01F01** Gestionar credenciales y cuentas.
- **R01F01T01** Adaptar acceso por usuario, altas controladas, cambio y recuperación de contraseña, desactivación y cierre de sesión.
- **R01F01T01P01** Probar acceso válido e inválido, recuperación docente y bloqueo de una cuenta desactivada con sesión previa. Estado: planificada.

- **R01F02** Aplicar roles y propiedad.
- **R01F02T01** Crear Policies y validación de campos para impedir elevación de rol y acceso a recursos ajenos.
- **R01F02T01P01** Comprobar que un alumno no entra en docencia y que un docente no modifica una clase ajena. Estado: planificada.

## R02 El profesor debe organizar a sus alumnos en clases.

- **R02F01** Gestionar clases y alumnos.
- **R02F01T01** Crear formularios y relaciones para clases propias, cuentas de alumnos y matrículas activas.
- **R02F01T01P01** Crear dos clases con propietarios distintos y verificar separación de sus alumnos. Estado: planificada.

- **R02F02** Gestionar altas y bajas.
- **R02F02T01** Sincronizar matrículas con inscripciones en asignaciones abiertas, conservando historial al desactivar.
- **R02F02T01P01** Comprobar que una baja bloquea el acceso y que una reincorporación conserva el avance previo. Estado: planificada.

## R03 El profesor debe poder preparar y distribuir una misión.

- **R03F01** Editar y validar borradores.
- **R03F01T01** Crear editor de misión y ordenación de nodos; validar contenido antes de publicar.
- **R03F01T01P01** Guardar, reabrir y reordenar un borrador; rechazar publicación con nodos incompletos. Estado: planificada.

- **R03F02** Publicar y asignar contenido.
- **R03F02T01** Publicar, duplicar, archivar y asignar misiones a clases propias; impedir edición de publicadas.
- **R03F02T01P01** Asignar a dos clases propias; rechazar una clase ajena y verificar que una copia no altera el original. Estado: planificada.

## R04 El alumno debe realizar actividades de repaso de varios tipos.

- **R04F01** Presentar recursos y tarjetas.
- **R04F01T01** Implementar explicación, vídeo validado con enlace alternativo y flashcards accesibles.
- **R04F01T01P01** Completar cada tipo; comprobar rechazo de vídeo inválido y de tarjetas ajenas al nodo. Estado: planificada.

- **R04F02** Corregir cuestionarios.
- **R04F02T01** Validar preguntas y opciones, corregir en servidor y guardar intentos y mejor nota.
- **R04F02T01P01** Probar 2 de 3 aciertos con umbral del 70 %, respuestas manipuladas y reintento posterior. Estado: planificada.

## R05 El alumno debe avanzar por etapas sin perder lo que ha completado.

- **R05F01** Aplicar los desbloqueos.
- **R05F01T01** Comprobar matrícula, asignación y nodo anterior al consultar o finalizar una actividad.
- **R05F01T01P01** Intentar abrir y completar un nodo bloqueado por URL y petición directa; debe rechazarse. Estado: planificada.

- **R05F02** Persistir progreso y puntos.
- **R05F02T01** Guardar una finalización por inscripción y nodo, con transacción y unicidad; calcular avance.
- **R05F02T01P01** Reenviar y duplicar simultáneamente la finalización; verificar un único premio y persistencia tras nuevo acceso. Estado: planificada.

## R06 El profesor debe conocer el avance de sus alumnos.

- **R06F01** Mostrar seguimiento individual y de clase.
- **R06F01T01** Consultar progreso, intentos y mejor nota filtrando por profesor, clase y asignación.
- **R06F01T01P01** Comparar el panel con resultados de dos alumnos conocidos y denegar datos de otro docente. Estado: planificada.

- **R06F02** Distinguir avance de calificación.
- **R06F02T01** Presentar porcentaje completado, nota y estados con etiquetas y filtros comprensibles.
- **R06F02T01P01** Verificar que un alumno con recursos vistos y quiz pendiente no figura como misión completada. Estado: planificada.

## R07 El profesor debe poder recibir ayuda de IA sin publicar contenido sin revisar.

- **R07F01** Generar un borrador estructurado.
- **R07F01T01** Crear servicio de API, esquema de salida, cuota, timeout y validación del resultado.
- **R07F01T01P01** Probar salida válida, salida inválida, cuota y error del proveedor; demostrar una llamada real. Estado: planificada.

- **R07F02** Revisar antes de publicar.
- **R07F02T01** Convertir la salida válida en borrador editable y exigir completar o retirar vídeos pendientes.
- **R07F02T01P01** Comprobar que generar no publica y que solo el propietario puede revisar y publicar. Estado: planificada.

## R08 El proyecto debe poder instalarse, probarse y explicarse con evidencias.

- **R08F01** Diseñar y documentar la solución.
- **R08F01T01** Mantener RFTP, casos de uso, diagramas, planificación, horas reales y memoria por hitos.
- **R08F01T01P01** Contrastar tareas cerradas con commits y evidencias, y totalizar horas y desviaciones. Estado: planificada.

- **R08F02** Verificar e instalar la aplicación.
- **R08F02T01** Preparar pruebas críticas, interfaz adaptable, README, contenedores, datos de demo y copia/restauración de la BD.
- **R08F02T01P01** Ejecutar pruebas, recorrer la demo con teclado y móvil, instalar desde cero y restaurar una copia. Estado: planificada.
