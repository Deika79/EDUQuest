# Decisiones técnicas

## Tomadas en la propuesta

- Aplicación web monolítica en un único repositorio.
- Servidor con PHP 8.4 y Laravel 13.
- Interfaz con Vue 3, TypeScript e Inertia.
- Estilos con Tailwind CSS y shadcn-vue del starter compatible.
- Base de datos MySQL 8.4 LTS.
- Autenticación basada en sesiones, cookies y protección CSRF.
- Autorización mediante Policies de Laravel.
- Un rol por usuario: administrador, docente o alumno.
- Registro público deshabilitado.
- Alta de docentes por administrador y alta de alumnos por docente.
- Misiones con estados borrador, publicada y archivada.
- Una misión publicada no se edita directamente; se duplica para modificarla.
- Progreso y puntos calculados en servidor.
- Cuestionarios corregidos en servidor; el navegador no envía nota final válida.
- IA solo para generar borradores revisables, nunca publicación automática.
- Docker Compose será opción preferida si Docker funciona en el entorno local.

## Cuestiones abiertas

- Fechas oficiales de propuesta, 50 %, 80 % y entrega final.
- Horas oficiales del módulo y criterio de registro exigido por el centro.
- Tecnologías obligatorias o prohibidas por el centro.
- Normas sobre uso de IA generativa en el proyecto y en la memoria.
- Tutor asignado y formato definitivo de la propuesta.
- Título definitivo del proyecto y disponibilidad del nombre si se publica.
- Proveedor de IA, modelo, coste y límites.
- Proveedor de despliegue, dominio y presupuesto real.
- Política de datos si se llegara a usar con menores reales.
- Formato exacto de evidencias que pedirá el centro.
