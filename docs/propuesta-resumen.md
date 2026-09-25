# EduQuest - Propuesta resumen

Estado: propuesta inicial para presentar al centro. No existe todavía aplicación implementada ni aprobación académica registrada.

## Idea

EduQuest es una aplicación web educativa para convertir repasos en misiones visuales. Un docente crea una misión con una secuencia de actividades, la asigna a una clase y consulta el avance de cada alumno. El alumno accede a su cuenta, abre un mapa de misión y desbloquea etapas al completar las anteriores.

El ejemplo de demo previsto es "Rescate en el planeta de las fracciones", una misión de cinco nodos con explicación, vídeo seleccionado por el profesor, tarjetas, cuestionario y cierre.

## Problema

Los docentes necesitan preparar repasos estructurados y seguir el progreso sin convertirlo en una plataforma compleja de videojuegos. EduQuest propone un flujo acotado: contenido propio o asistido por IA, revisión docente, avance secuencial y evidencias de progreso.

No se afirmará que la aplicación mejora el aprendizaje o la motivación sin evaluación real con alumnado. Esa mejora queda como hipótesis.

## Usuarios

- Administrador: crea o desactiva docentes.
- Docente: gestiona sus clases, alumnos, misiones, asignaciones, generación asistida y seguimiento.
- Alumno: realiza misiones asignadas y consulta su propio avance.

## Alcance del MVP

Incluido:

- Autenticación, cierre de sesión, cambio y recuperación de contraseña.
- Tres roles con permisos comprobados en servidor.
- Alta controlada de docentes y alumnos.
- Clases propias, matrículas y asignaciones.
- Misiones con estado borrador, publicada y archivada.
- Editor por formularios con nodos ordenables.
- Cuatro tipos de nodo: explicación, vídeo, cuestionario y flashcards.
- Publicación, vista previa, duplicación, archivo y asignación.
- Mapa secuencial con alternativa accesible en lista.
- Progreso persistente, reintentos, puntos personales y panel docente.
- Generación de borradores con IA, siempre revisados antes de publicar.
- Pruebas, instalación reproducible, demo y documentación.

Fuera del MVP:

- Ramificaciones, editor libre con arrastrar nodos, multijugador, combate, economía virtual, rankings públicos, chat, familias, push, app móvil, suscripciones, marketplace, SCORM y tutor conversacional para alumnos.
- Subida o transcodificación de vídeo.
- Búsqueda automática de vídeos con IA.

## Tecnologías propuestas

- Servidor: PHP 8.4 y Laravel 13.
- Interfaz: Vue 3, TypeScript, Inertia, Tailwind CSS y shadcn-vue.
- Base de datos: MySQL 8.4 LTS.
- Autenticación: starter oficial de Laravel con Fortify y sesiones.
- Autorización: Policies de Laravel.
- Pruebas: Pest para servidor y Playwright para recorridos críticos.
- Calidad: Laravel Pint, ESLint, TypeScript y Prettier.
- Entorno: Git, VS Code, Codex y Docker Compose si está disponible.

Las versiones efectivas se fijarán cuando se autorice el arranque técnico y se generen los lockfiles.
