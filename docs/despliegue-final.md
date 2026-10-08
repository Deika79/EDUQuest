# Preparacion tecnica de despliegue externo

Fecha de revision: 2026-10-07.

Este documento recoge una configuracion reproducible de produccion ensayada en local. No acredita despliegue externo, dominio, certificado, tunel ni VM creada.

## Alcance

- Preparar una imagen separada de Sail para Laravel 13 con Apache, PHP 8.4, vendor de Composer sin dependencias de desarrollo y frontend compilado.
- Arrancar MySQL 8.4 en una red Compose interna, sin publicar su puerto al host ni a Internet.
- Ensayar la instalacion con proyecto Compose, red y volumen nuevos: `eduquest_prod_rehearsal`.
- Mantener intacta la aplicacion local Sail y la base habitual.

## Diferencias con Sail

| Area       | Sail local                                                       | Produccion preparada                                                                                  |
| ---------- | ---------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------- |
| Imagen PHP | `vendor/laravel/sail/runtimes/8.4`, orientada a desarrollo.      | `Dockerfile.prod` con `php:8.4-apache-bookworm`, extensiones necesarias y Apache sirviendo `public/`. |
| Codigo     | Montaje caliente `.:/var/www/html`.                              | Codigo copiado dentro de la imagen; sin montaje del arbol de trabajo.                                 |
| Frontend   | Vite en desarrollo y puerto `5173`.                              | `npm run build` dentro de la imagen; no se ejecuta servidor Vite.                                     |
| MySQL      | `mysql:8.4` con puerto opcional publicado por `FORWARD_DB_PORT`. | `mysql:8.4` solo en red interna Compose; `3306/tcp` no se publica al host.                            |
| Variables  | `.env` local.                                                    | `.env.production` privado a partir de `.env.production.example`; se invoca con `--env-file`.          |
| HTTPS      | No aplica en local.                                              | Preparado para poner un proxy HTTPS delante; no se simula dominio ni certificado.                     |

## Archivos creados

- `.dockerignore`: excluye secretos, dependencias locales, caches, `public/build`, capturas del hito 80 y assets no versionados.
- `Dockerfile.prod`: multi-stage con Composer, build frontend con PHP 8.4 para Wayfinder, y runtime Apache.
- `compose.prod.yml`: app y MySQL en red propia, volumen `eduquest-prod-mysql`, puerto HTTP configurable y MySQL sin publicacion externa.
- `.env.production.example`: plantilla sin secretos para produccion.
- `docker/production/000-default.conf`: VirtualHost Apache para `public/`.
- `docker/production/entrypoint.sh`: prepara caches/permisos, acepta comandos `artisan` y optimiza Laravel al arrancar.
- `docker/production/php.ini`: limites y OPcache para produccion.

## Variables

La plantilla `.env.production.example` documenta:

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_KEY` vacia para que se genere un valor real fuera de Git.
- `APP_URL` y `APP_HTTP_BIND`/`APP_HTTP_PORT`.
- Conexion MySQL interna mediante `DB_HOST=mysql`.
- `SESSION_DRIVER=database`.
- Correo por `log` hasta configurar proveedor real.
- IA sin clave y con `AI_GENERATION_DAILY_LIMIT=0` para poder desactivar la generacion en demo externa.

No se copiaron secretos del `.env` habitual, no se versiono `.env.production` y no se hicieron llamadas reales a proveedores de IA.

## Ensayo local aislado

URL usada: `http://localhost:18081`.

Comandos principales ejecutados:

```powershell
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal up -d --build
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal exec -T app php artisan migrate --force
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal exec -T app php artisan db:seed --class=CosmeticCatalogSeeder --force
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal exec -T app php artisan optimize:clear
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal exec -T app php artisan optimize
docker compose --env-file .env.production -f compose.prod.yml -p eduquest_prod_rehearsal restart
```

Incidencias corregidas durante el ensayo:

- El build frontend fallaba porque Wayfinder ejecuta `php artisan` durante `npm run build`; se cambio el stage frontend para incluir PHP 8.4, vendor y `artisan`.
- Una cache local de Laravel referenciaba paquetes de desarrollo ausentes en `--no-dev`; la imagen limpia `bootstrap/cache/*.php`.
- Wayfinder necesitaba `storage/framework/views`; el stage frontend crea los directorios de cache antes de generar rutas.
- El puerto `18080` estaba ocupado en la maquina; el ensayo privado uso `APP_HTTP_PORT=18081`.
- Un primer intento del seeder de catalogo se lanzo en paralelo con migraciones y fallo porque la tabla aun no existia; repetido en orden, finalizo correctamente.

Resultados comprobados:

| Comprobacion         | Resultado                                                                                                             |
| -------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Build imagen app     | Correcto. Imagen local `eduquest/app:prod-local`.                                                                     |
| Arranque Compose     | Correcto con app y MySQL saludables.                                                                                  |
| Puerto app           | `127.0.0.1:18081->80/tcp`.                                                                                            |
| Puerto MySQL         | No publicado al host; solo `3306/tcp` y `33060/tcp` internos.                                                         |
| Migraciones          | 17 migraciones ejecutadas correctamente, incluida `2026_10_06_120000_add_map_theme_to_missions_table`.                |
| Catalogo cosmetico   | `CosmeticCatalogSeeder`: correcto tras ejecutar en orden.                                                             |
| Datos demo ficticios | 2 usuarios, 5 misiones, 5 inscripciones, 6 cosmeticos y 0 progresos en la base aislada.                               |
| Landing              | `GET /`: HTTP 200, 6452 bytes.                                                                                        |
| Login                | `GET /login`: HTTP 200, 7242 bytes.                                                                                   |
| Sesion alumno        | Login HTTP con `alumno_demo` aislado y `GET /student/missions`: HTTP 200, 8631 bytes.                                 |
| Paginas de mapa      | `/student/missions/1`, `/2` y `/3`: HTTP 200 autenticado.                                                             |
| Assets mapa          | `fantasy_map.png` 200 / 3447955 bytes; `science_map.png` 200 / 3622025 bytes; `old_west_map.png` 200 / 3653787 bytes. |
| Frontend compilado   | `GET /build/manifest.json`: HTTP 200, 22308 bytes.                                                                    |
| Persistencia         | Antes y despues de reiniciar: `{"users":2,"missions":5,"enrollments":5,"cosmetics":6,"progress":0}`.                  |

Los datos demo se prepararon en la base aislada. El comando `eduquest:prepare-demo` esta protegido para `local`/`testing`; para este ensayo se ejecuto con `APP_ENV=local` solo en ese proceso y despues se regeneraron caches de produccion. No se recomienda usar ese atajo como operacion ordinaria en una produccion real.

## Compatibilidad ARM64

No se construyo ni ejecuto una imagen ARM64 real en esta maquina. Se comprobaron los manifiestos multi-arquitectura publicados con `docker buildx imagetools inspect` y todos incluyen `linux/arm64/v8`:

- `php:8.4-apache-bookworm`.
- `php:8.4-cli-bookworm`.
- `node:24-bookworm-slim`.
- `composer:2`.
- `mysql:8.4`.

La prueba real ejecutada aqui fue local x86/amd64 sobre Docker Desktop.

## Actualizacion

Procedimiento previsto para una instalacion ya creada:

```powershell
git pull
docker compose --env-file .env.production -f compose.prod.yml build --pull app
docker compose --env-file .env.production -f compose.prod.yml up -d
docker compose --env-file .env.production -f compose.prod.yml exec -T app php artisan migrate --force
docker compose --env-file .env.production -f compose.prod.yml exec -T app php artisan db:seed --class=CosmeticCatalogSeeder --force
docker compose --env-file .env.production -f compose.prod.yml exec -T app php artisan optimize
```

Antes de actualizar, hacer copia de base de datos y conservar la revision Git anterior para poder volver atras.

## Copia y restauracion

Crear copia desde el contenedor MySQL:

```powershell
docker compose --env-file .env.production -f compose.prod.yml exec -T mysql sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' > backups/eduquest.sql
```

Restaurar en una instalacion aislada o en una ventana de mantenimiento:

```powershell
docker compose --env-file .env.production -f compose.prod.yml exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' < backups/eduquest.sql
docker compose --env-file .env.production -f compose.prod.yml exec -T app php artisan optimize
```

No ejecutar restauraciones sobre la base habitual sin copia previa y autorizacion expresa.

## HTTPS futuro

La configuracion actual deja la app escuchando por defecto en `127.0.0.1:${APP_HTTP_PORT}` para que un proxy de la VM publique HTTPS. Quedan pendientes decisiones que requieren una VM y una direccion publica reales:

- Elegir proxy HTTPS: Caddy, Nginx o Traefik.
- Configurar dominio real o subdominio y DNS.
- Abrir reglas de firewall y security list de Oracle Cloud solo para HTTP/HTTPS.
- Emitir certificado TLS real.
- Definir politica de backups externos y rotacion.
- Decidir si la demo externa mantendra IA desactivada o usara una clave real fuera de Git.

## Pasos pendientes para una VM Always Free

1. Crear la VM ARM64 en Oracle Cloud y registrar su IP publica.
2. Instalar Docker Engine y Docker Compose plugin en la VM.
3. Clonar el repositorio y cambiar a la revision publicada.
4. Crear `.env.production` desde `.env.production.example` con `APP_KEY`, `APP_URL`, passwords de MySQL y configuracion de correo/IA sin versionar.
5. Arrancar `docker compose --env-file .env.production -f compose.prod.yml up -d --build`.
6. Ejecutar migraciones, catalogo y, si procede, preparar datos demo en un entorno controlado.
7. Comprobar landing, login, rutas autenticadas, mapas y assets.
8. Configurar proxy HTTPS, DNS, certificado y firewall.
9. Probar copia y restauracion en otro volumen o instancia antes de abrir la demo.

Hasta completar esos pasos en una VM real, EDUQuest no debe describirse como desplegado externamente.

## Prueba externa temporal con Cloudflare Quick Tunnel

Fecha de prueba: 2026-10-07.

Esta prueba sirve solo para revisar acceso externo temporal desde el ordenador de desarrollo. No sustituye una VM, no reserva dominio, no ofrece garantia de disponibilidad y deja de funcionar al cerrar `cloudflared`, apagar el equipo o perder conectividad. La URL temporal no se versiona porque cambia en cada ejecucion y no es alojamiento permanente.

Auditoria previa realizada antes de abrir el tunel:

- `.env.production` esta ignorado por Git mediante `.gitignore` y no aparece en `git ls-files`.
- `.env.production.example` es la unica plantilla de entorno versionada y no contiene secretos.
- `APP_ENV=production`, `APP_DEBUG=false`, `OPENAI_API_KEY=` y `AI_GENERATION_DAILY_LIMIT=0` en el entorno aislado.
- `GET /register` en `127.0.0.1:18081` devuelve `404`.
- La base aislada contiene solo usuarios ficticios: docente `carlinchis` con nombre `Docente Demo Externo` y alumno `alumno_demo` con nombre `Alumno Demo`.
- `eduquest_prod_rehearsal-mysql-1` no publica puerto al host; solo queda visible en la red Compose.

Herramienta usada:

```powershell
New-Item -ItemType Directory -Force -Path tmp\cloudflared | Out-Null
curl.exe -L "https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe" -o "tmp\cloudflared\cloudflared.exe"
tmp\cloudflared\cloudflared.exe --version
```

Version comprobada: `cloudflared 2026.10.0`. El binario se dejo en `tmp/` y no se instalo globalmente ni se modifico configuracion personal.

Comando de arranque temporal:

```powershell
tmp\cloudflared\cloudflared.exe tunnel --no-autoupdate --url http://127.0.0.1:18081 --loglevel info
```

Para repetir la prueba en PowerShell manteniendo logs:

```powershell
$log = Join-Path (Get-Location) "tmp\cloudflared\quick-tunnel.log"
$err = Join-Path (Get-Location) "tmp\cloudflared\quick-tunnel.err.log"
$exe = Join-Path (Get-Location) "tmp\cloudflared\cloudflared.exe"
Start-Process -FilePath $exe -ArgumentList @("tunnel","--no-autoupdate","--url","http://127.0.0.1:18081","--loglevel","info") -RedirectStandardOutput $log -RedirectStandardError $err -WindowStyle Hidden
Get-Content $err -Wait
```

Ajuste necesario detectado:

- Sin proxy confiable, Laravel generaba enlaces de CSS/JS con `http://...trycloudflare.com`, lo que produciria contenido mixto en navegador.
- Se anadio `config/trustedproxy.php` para leer `TRUSTED_PROXIES` desde entorno.
- En el ensayo aislado, `.env.production` usa `TRUSTED_PROXIES=*` solo para el tunel temporal. La plantilla recomienda `REMOTE_ADDR` para un proxy local unico, CIDR conocidos en produccion real o `*` solo en pruebas controladas.

Resultados por HTTPS temporal:

| Comprobacion                                  | Resultado                                                                                           |
| --------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| Landing `/`                                   | HTTP 200, 7220 bytes tras confiar proxy.                                                            |
| Login `/login`                                | HTTP 200, 8234 bytes tras confiar proxy.                                                            |
| CSS principal                                 | HTTP 200, 125350 bytes.                                                                             |
| JS principal                                  | HTTP 200, 145416 bytes.                                                                             |
| Ilustracion `aventura-entre-mundos.png`       | HTTP 200, 3040466 bytes.                                                                            |
| Trailer `eduquest-trailer.mp4`                | HTTP 206 con rango 0-1023, 1024 bytes.                                                              |
| Mapa fantasia                                 | Cabecera HTTP 200, `Content-Length: 3447955`.                                                       |
| Mapa ciencia ficcion                          | Cabecera HTTP 200, `Content-Length: 3622025`.                                                       |
| Mapa oeste                                    | Cabecera HTTP 200, `Content-Length: 3653787`.                                                       |
| Ruta protegida sin sesion `/student/missions` | Redirige a `/login` por HTTPS con 1 redireccion.                                                    |
| Cookies de login GET                          | `XSRF-TOKEN` y `eduquest-session` se emiten con `Secure` y `SameSite=Lax`; la sesion es `HttpOnly`. |

Limitaciones observadas:

- La descarga simultanea de los tres mapas completos por Quick Tunnel produjo timeouts de QUIC alrededor de 1 MiB, pero el tunel se reconecto y las cabeceras de cada imagen confirmaron HTTP 200 y longitud correcta. Para demo visual, probar los mapas de uno en uno.
- El 2026-10-08 se diagnostico una incidencia real de demo: la URL temporal anterior dejo de responder desde el exterior aunque el proceso local de `cloudflared` seguia activo. Se cerro el tunel anterior, se abrio uno nuevo contra `http://127.0.0.1:18081` y se repitio la prueba. La landing respondio `200`, `/login` respondio `200` sin redirecciones, Chrome headless confirmo que los botones `Acceder` y `Entrar en EDUQuest` hidratan con `href="/login"`, y los logs no mostraron peticiones graficas a `/login` en la visita anterior. La causa demostrada fue la caducidad/desconexion operativa de la URL temporal, no un enlace roto de la landing.
- En la misma prueba del 2026-10-08 se verifico el flujo HTTPS completo con una cuenta ficticia efimera creada y eliminada en `eduquest_prod_rehearsal`: `GET /login` `200`, cookies `XSRF-TOKEN` y `eduquest-session` para el dominio `trycloudflare.com` con `Secure`, `POST /login` `302` a `/dashboard`, `/student/missions` autenticado `200`, mapa de alumno `200`, `POST /logout` `302` y `/student/missions` sin sesion redirigiendo a `/login`. No se observaron errores `419`, `500`, credenciales invalidas ni bucles de redireccion.
- `alumno_demo` permanece activo en la base ficticia, con rol `student`, cambio obligatorio de contrasena desactivado, avatar configurado y cinco inscripciones activas. La contrasena no se documenta ni se guarda en archivos.

Como detenerlo:

```powershell
Get-Process cloudflared | Stop-Process
```

Al detener `cloudflared`, la URL temporal deja de responder. Los contenedores y el volumen MySQL aislado no se borran; solo se corta el acceso externo.

Resultado comprobado en esta sesion: tras detener el proceso, la URL temporal anterior devolvio `530` de Cloudflare y la base aislada conservo `users=2`, `missions=5`, `enrollments=5` y `progress=0`.

## Comprobaciones complementarias

- `npm run types:check`: correcto en host.
- `npm run check`: fallo en host antes de analizar codigo por ausencia del binding opcional nativo `@voidzero-dev/vite-plus-win32-x64-msvc` en `node_modules`.
- `docker run --rm eduquest/frontend-check:local npm run types:check`: correcto.
- `docker run --rm eduquest/frontend-check:local npm run check`: no es una senal valida de aplicacion porque el stage de produccion incluye `vendor/` y `public/build`; `vp check` intento formatear artefactos de dependencias y build, y fallo sobre plantillas/archivos externos.
