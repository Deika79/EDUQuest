# Identidad visual de EDUQuest

Direccion aprobada para el hito del 80 %: **Un camino, muchos mundos**.

## Fuentes conservadas

El paquete original recibido se conserva en `docs/identidad/EDUQuest_identidad_v01/`. Incluye las fuentes SVG y los raster originales usados para preparar los recursos web.

En el paquete disponible durante la integracion no estaban presentes la guia de identidad ni `vista-previa.html` mencionados en la tarea. No se ha reconstruido ni publicado esa vista previa. La integracion se apoya en los recursos recibidos y en las decisiones visibles en ellos:

- azul noche `#101d36` para fondos y estructura;
- ambar `#f5b845` y su variante de texto accesible `#a3610d`;
- turquesa `#4dd0c2` y su variante de texto accesible `#167c76`;
- simbolo de recorrido en forma de Q;
- lema «Un camino, muchos mundos».

## Recursos de produccion

`public/brand/` contiene unicamente los archivos que consume la aplicacion:

- `simbolo.svg`;
- `logo-horizontal-claro.svg` y `logo-horizontal-oscuro.svg`;
- `favicon.svg`, `favicon-32.png` y `favicon-512.png`;
- `hero-mundos-1600x900.webp` para la landing;
- `portada-alumno-1280x448.webp` para el area del alumno;
- `eduquest-trailer.mp4` para el hero de la landing.

Las dos imagenes WebP se generaron desde los raster originales con FFmpeg. La portada web esta recortada para dejar el texto como contenido HTML accesible y pesa aproximadamente 49 KB frente a los 1,89 MB del PNG original; la imagen de mundos pesa aproximadamente 166 KB frente a los 2,43 MB originales.

El trailer web se genero con FFmpeg desde el original HEVC de 26,73 MB. La variante publicada usa H.264, `yuv420p`, 1600 x 900 a 30 fps, inicio rapido y audio AAC estereo a 44,1 kHz. Dura 24,29 segundos y ocupa 5.513.692 bytes. El original se conserva localmente en `docs/identidad/originales-locales/eduquest-trailer-hevc.mp4` y esta excluido de Git por su peso.
