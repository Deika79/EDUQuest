# Evidencias visuales finales

## Capturas autenticadas del 2026-11-05

Se reutilizaron las misiones existentes `Revision ciencia` y `Revision oeste`, asignadas a la cuenta ficticia `revision_final_alumno`. No fue necesario crear misiones ni usar generación IA. Las misiones de `alumno_demo` no se modificaron.

| Archivo | Ruta local | Vista emulada | Tamaño PNG |
| --- | --- | --- | --- |
| `final-mapa-ciencia-ui-desktop.png` | `/student/missions/11` | 1440 × 1100 | 1440 × 2238 |
| `final-mapa-ciencia-ui-mobile.png` | `/student/missions/11` | 390 × 1000 | 390 × 1496 |
| `final-mapa-oeste-ui-desktop.png` | `/student/missions/12` | 1440 × 1100 | 1440 × 2238 |
| `final-mapa-oeste-ui-mobile.png` | `/student/missions/12` | 390 × 1000 | 390 × 1496 |

Las capturas completas se realizaron en Chrome sobre `http://localhost:8080`, con imágenes y fuentes cargadas. Se comprobaron visualmente el tema, el progreso inicial, los cuatro nodos, la ruta y la lista de actividades; el documento no presentó desbordamiento horizontal en los dos anchos comprobados. No se completaron actividades ni se alteró el progreso para obtener estas imágenes. La vista móvil corresponde a emulación, no a dispositivo físico. La sesión local temporal se revocó y su archivo se eliminó después de las capturas.

## Imágenes aportadas por los autores

- `final-landing-escritorio.png`: interfaz pública de la landing.
- `final-mapa-fantasia-escritorio.png`: vista parcial del mapa y actividades completadas.
- `final-mapa-fantasia-movil.png`: vista parcial en formato móvil; no permite certificar un ancho de dispositivo.
- `final-mapa-ciencia-ficcion-escritorio.png` y `final-mapa-oeste-escritorio.png`: ilustraciones de fondo, conservadas como recursos de diseño. Las capturas autenticadas anteriores sustituyen su uso como evidencia de interfaz.

La memoria incorpora las tres primeras imágenes aportadas y las cuatro capturas autenticadas. Los PNG originales permiten ampliar detalles que quedan reducidos al insertarlos en una página de Word.

## Regeneración de la memoria

Desde la raíz del proyecto, con PowerShell y Microsoft Word instalados:

```powershell
pwsh -File scripts/build-memoria-final-ooxml.ps1 -ReviewInWord
```

La misma vía genera el DOCX, actualiza su índice y renderiza las páginas en `tmp/` para revisión visual. El documento sigue siendo una memoria de trabajo con campos académicos y propuestas del anexo A pendientes de validación.
