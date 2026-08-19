---
id: S-DO-01
name: Entorno reproducible
role: devops
repository: ventas-inventario
status: EN_PROGRESO
branch: sprint/S-DO-01
base_sha: b99b93667d47bf49b18f8febabc5b0541f2df579
final_sha: null
worktree_path: /private/tmp/claude-501/-Users-sankef-ventas-inventario/8fefec88-810c-4dd5-b0d7-6da99cf44f83/scratchpad/S-DO-01
updated_at: 2026-08-19
---

## Objetivo

Dejar el sistema levantable con un comando: aplicación, PostgreSQL y proceso
trabajador de la cola, en contenedores, con datos que sobreviven al reinicio y
sin ningún trámite manual de credenciales. Solo entorno local de desarrollo: sin
despliegue, sin integración continua, sin respaldos y sin observabilidad, que es
lo que el RFC declara explícitamente fuera de alcance y le corresponde a S-DO-02.

## Implementado

| Unidad | Qué quedó | Estado |
|---|---|---|
| UT-01 | Imagen de la aplicación sobre `php:8.5.9-cli` con las extensiones que el proyecto necesita, más Composer, Node y pnpm en las versiones que S-00 verificó | verificado |
| UT-02 | Servicio `db` sobre `postgres:18.3-alpine` con volumen propio, sin publicar puerto, y con la base de pruebas creada al inicializarse | verificado |
| UT-03 | Servicio `trabajador` con `queue:work`, reintentos de espera creciente y reinicio automático ante caída del proceso | verificado |
| UT-04 | Texto de la sección "Cómo correrlo" redactado abajo — lo aplica el Coordinador, porque `README.md` no es ruta de este rol | pendiente de aplicar |

### Archivos

- `Dockerfile` — imagen de desarrollo de la aplicación.
- `docker-compose.yml` — los tres servicios, su red, sus volúmenes y su configuración.
- `docker/entrypoint.sh` — preparación del contenedor: dependencias, clave, assets y migraciones.
- `docker/postgres/10-crear-base-de-pruebas.sh` — crea `ventas_inventario_test` al inicializar el volumen.
- `.dockerignore` — contexto de build mínimo.

### Decisiones que conviene no redescubrir

1. **El puerto de PostgreSQL no se publica al host.** La máquina ya tiene una
   instalación local ocupando el 5432. Publicar en otro puerto habría evitado la
   colisión igual, pero no publicarlo hace además imposible que este contenedor
   escriba por error en `ventas_inventario` o `ventas_inventario_test` de la
   instalación local, que son de otro sprint.
2. **La aplicación se publica en 8080, no en 8000.** El 8000 es el que usa
   `php artisan serve` en la instalación local y este entorno no tiene por qué
   quitárselo. Escucha solo en `127.0.0.1`.
3. **La imagen no copia el código: el proyecto se monta.** Es una imagen de
   desarrollo y editar un archivo tiene que verse sin reconstruir. La imagen de
   despliegue, que sí debe ser autocontenida, es de S-DO-02.
4. **El servidor web es `php -S`, no `php artisan serve`.** Ver el hallazgo 3.
5. **La credencial de la base del contenedor es un literal en
   `docker-compose.yml`.** Es local y desechable: el puerto no se publica y la
   base se recrea con `docker compose down -v`. Arquitectura fijó su posición —
   no viola RNF-014 — con la condición de que el compose de un entorno servido
   no herede el patrón. Está advertido en el propio archivo y en el README.
6. **`APP_KEY` no se versiona.** Se genera en el primer arranque y queda en el
   `.env`, que está en `.gitignore`.
7. **El compose no fija un nombre de proyecto.** Con un nombre fijo, dos copias
   del repositorio en la misma máquina —el worktree de otro sprint, el checkout
   con el que QA valida— compartirían contenedores y volúmenes, y la segunda en
   levantar el entorno se llevaría por delante a la primera sin avisar. Compose
   lo deriva del directorio, así que cada copia queda aislada. Es la misma
   preocupación que resolvió lo del puerto 5432, aplicada al propio Compose.

## Checkpoints

### 2026-08-19 — cinco fallos reales encontrados y corregidos

Ninguno se vio leyendo la configuración; todos aparecieron al ejecutarla.

1. **`opcache` rompía el build de la imagen.** Ya viene compilada en
   `php:8.5.9-cli`, así que `docker-php-ext-install opcache` no encuentra módulo
   que instalar y aborta. Se quitó de la lista.
2. **PostgreSQL 18 no arrancaba con la ruta de datos de siempre.** Desde la
   versión 18 estas imágenes guardan los datos en un subdirectorio con el número
   de versión mayor, para permitir `pg_upgrade` sin cruzar el punto de montaje.
   El volumen va en `/var/lib/postgresql`, no en `/var/lib/postgresql/data`.
3. **`php artisan serve` reinyectaba el `.env` y pisaba la configuración del
   contenedor.** El síntoma era de los peores posibles: `php artisan tinker`
   conectaba a la base del contenedor y respondía bien, mientras el navegador
   fallaba contra `127.0.0.1`. Es la misma clase de divergencia entre
   configuración declarada y conexión real que QA encontró en S-00, por otro
   camino. Se resolvió sirviendo con `php -S` y el enrutador del framework.
4. **Faltaba la extensión `pcntl`.** Sin ella `queue:work` no atiende señales:
   no se apaga ordenadamente y no puede aplicar el tiempo límite por trabajo.
   No se nota al arrancar, solo al apagar o reiniciar.
5. **La clave de la aplicación era distinta entre procesos.** La primera versión
   la exportaba desde el entrypoint, y una variable exportada ahí solo la ve el
   proceso que ese script lanza: `docker compose exec` no pasa por el entrypoint.
   Resultado real y reproducido: un trabajo encolado desde una sesión `exec`
   quedaba firmado con otra clave y el trabajador lo rechazaba con
   `InvalidSignatureException`. Ahora la clave vive en el `.env` y la leen todos
   los procesos por igual.

Además, el entorno dejaba un `.pnpm-store` de 79 MB dentro del repositorio de
quien programa, porque pnpm ubica su almacén en el sistema de archivos de
`node_modules` y ese vive en un volumen. Se corrigió apuntando el almacén dentro
del propio volumen.

### 2026-08-19 — un falso verde detectado a tiempo

En una corrida intermedia la raíz respondía **200 y el contenedor figuraba
`healthy`**, pero el cuerpo no era la página: era una advertencia de PHP
(`require_once(/app/index.php): Failed to open stream`). El enrutador del
framework resuelve sus rutas contra `getcwd()`, así que el servidor tiene que
arrancar parado en `public/`; `-t public` no alcanza.

Lo importante no es el error sino cómo se veía: el código de respuesta decía que
todo estaba bien. Por eso el healthcheck ahora comprueba el **contenido**
(`Application up`) y no solo el código, y por eso la evidencia de abajo verifica
que la raíz sirve los assets compilados en vez de conformarse con un 200.

## Evidencia de verificación

Todo sobre `ventas-inventario@<final_sha>`, imagen `ventas-inventario-app`
`sha256:9c3361b8b33a0dc57427bc017001347a2b57bc86b6a1713bd023b2d69a1e3988`.
Partiendo de un árbol sin `vendor/`, sin `node_modules/`, sin `public/build/` y
sin `.env`, y con `docker compose down -v` previo.

| Qué se comprobó | Cómo | Resultado |
|---|---|---|
| Un solo comando levanta todo | `docker compose up -d --build` | los tres servicios arriba en **35 s** (con las imágenes base ya descargadas; la primera vez tarda más) |
| UT-01 — la imagen construye y responde | `docker compose exec app php artisan --version` | `Laravel Framework 13.26.1` sobre PHP 8.5.9 |
| UT-01 — extensiones de la firma y del motor | `php -m` dentro del contenedor | `bcmath dom intl openssl pcntl pdo_pgsql pgsql soap zip` |
| La raíz sirve la aplicación, no una advertencia | `curl http://127.0.0.1:8080/` | 200, con el HTML del layout y la referencia a `build/assets/app-*` |
| La aplicación habla con la base del contenedor | `select current_database(), inet_server_addr()` | `ventas_inventario @ 172.24.0.2` — la red interna, no el host |
| UT-02 — el dato sobrevive | insertar, `docker compose down`, `up`, leer | el dato vuelve a leerse tras destruir y recrear los contenedores |
| UT-02 — base de pruebas creada | `pg_database` del contenedor | `ventas_inventario`, `ventas_inventario_test` |
| UT-03 — el trabajador procesa | encolar un trabajo y leer el log | `DONE`, y la línea aparece en `laravel.log` |
| UT-03 — vuelve solo tras caerse | matar el proceso dentro del contenedor | PID 70419 → 70838, `RestartCount` 1, `running`; y vuelve a procesar (1 → 2 trabajos) |
| `./vendor/bin/pint --test` | dentro del contenedor | PASA — 27 archivos |
| `php artisan test --testsuite=Unit` | dentro del contenedor | 4 passed |
| `php artisan test --testsuite=Feature` | dentro del contenedor, con `-e DB_DATABASE=ventas_inventario_test` | 7 passed |
| `pnpm build` | dentro del contenedor | PASA |
| La base de pruebas queda sana tras la suite | `select count(*) from migrations` | 3 migraciones |
| La guardia de `DB_URL` funciona | agregar `DB_URL=` al `.env` y levantar | el arranque se detiene con el mensaje que nombra la causa |
| Nada del host se tocó | `docker compose ps`, puerto 5432 del host | ningún servicio publica 5432; la instalación local sigue escuchando |
| El repositorio queda limpio | `git status --porcelain` | solo los archivos nuevos del sprint |

Ninguna credencial real pasó por este sprint. La única contraseña que aparece es
la de la base contenerizada, local y desechable, y está en `docker-compose.yml`
con la advertencia de que un entorno servido no puede heredar ese patrón.

## Texto para UT-04 — sección "Cómo correrlo" del README

`README.md` no es ruta escribible del rol `devops`, así que este texto lo aplica
el Coordinador. Reemplaza la sección "Cómo correrlo" actual; el bloque de
instalación local que hoy está ahí se conserva como segunda opción.

---

### Cómo correrlo

Hay dos formas. **Con Docker** no necesitas instalar nada más que Docker, y es
la recomendada. **Sin Docker** necesitas PHP, PostgreSQL, Composer y pnpm
instalados en tu máquina.

#### Con Docker (recomendado)

Necesitas Docker Desktop —o Docker Engine con el complemento Compose— y nada más.

```bash
docker compose up -d
```

Eso es todo. Ese comando construye la imagen, levanta PostgreSQL, instala las
dependencias de PHP y de Node, genera la clave de la aplicación, compila los
assets, aplica las migraciones y arranca el servidor y el proceso trabajador de
la cola. La primera vez tarda varios minutos porque descarga las imágenes y
compila las extensiones de PHP; las siguientes son cuestión de segundos.

Cuando termine, la aplicación está en **<http://localhost:8080>**.

Para ver qué está pasando mientras arranca:

```bash
docker compose logs -f app
```

**Por qué el 8080 y no el 8000.** El 8000 es el que usa `php artisan serve` si
trabajas sin Docker, y el entorno contenerizado lo deja libre para que puedas
tener las dos cosas a la vez sin que se peleen.

**El proceso trabajador ya está corriendo.** No hace falta lanzar
`php artisan queue:work` a mano: el servicio `trabajador` lo levanta y lo vuelve
a levantar solo si se cae. Es el proceso que envía los comprobantes a SUNAT
fuera de la venta; si no corre, los comprobantes se quedan en `PENDIENTE`. Para
mirar lo que hace:

```bash
docker compose logs -f trabajador
```

**Comandos dentro del contenedor.** Cualquier comando del proyecto se ejecuta
con `docker compose exec app`:

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pint --test
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app pnpm build
```

Las pruebas de la suite `Feature` necesitan que le indiques la base de pruebas,
porque el contenedor define la base de la aplicación como variable de entorno y
esa tiene prioridad sobre la configuración de PHPUnit:

```bash
docker compose exec -e DB_DATABASE=ventas_inventario_test app php artisan test --testsuite=Feature
```

Si te olvidas, la suite no corre contra la base equivocada: se detiene con un
mensaje que te dice a qué base se conectó. Está hecho a propósito.

**La base de datos.** Corre dentro de Docker y **no publica ningún puerto**, así
que no interfiere con un PostgreSQL que tengas instalado en tu máquina, ni puede
escribir por error en sus bases. Para abrir una sesión contra ella:

```bash
docker compose exec db psql -U ventas_inventario -d ventas_inventario
```

Los datos viven en un volumen de Docker y sobreviven a `docker compose down` y a
reiniciar la máquina. Para empezar de cero y borrarlos:

```bash
docker compose down -v
```

La contraseña de esa base está escrita en `docker-compose.yml`. **Es local y
desechable**: no da acceso a nada fuera de tu máquina, porque el puerto no se
publica y la base se recrea con el comando de arriba. No la copies a un servidor
ni la tomes como ejemplo de cómo configurar uno.

**Si ya tenías un `.env`**, se respeta tal cual y no se toca. La conexión a la
base la define `docker-compose.yml`, así que tu `.env` puede seguir apuntando a
tu instalación local sin romper nada. La única excepción es `DB_URL`: si la
tienes definida con un valor, el contenedor se detiene y te lo dice, porque esa
variable tiene prioridad sobre todas las demás y te conectaría a otro sitio sin
avisar.

**Este entorno es para desarrollar en tu máquina.** No sirve como base para
poner el sistema en un servidor: no tiene HTTPS, ni respaldos, ni manejo de
secretos, y el servidor web es el de desarrollo de PHP. El despliegue es otro
trabajo.

#### Sin Docker

Necesitas PostgreSQL corriendo, con un rol y una base para la aplicación y otra
base de pruebas terminada en `_test`. Los nombres por defecto van en
`.env.example`.

```bash
cp .env.example .env   # completar los valores requeridos; .env nunca se commitea
composer install
pnpm install
php artisan key:generate
php artisan migrate
pnpm build             # o `pnpm dev` mientras desarrollas
php artisan serve
```

`pnpm build` no es opcional: sin los assets compilados, Vite no encuentra su
manifiesto y cualquier vista que extienda el layout base responde 500.

El envío de comprobantes a SUNAT corre en segundo plano, así que además del
servidor web hace falta el proceso trabajador:

```bash
php artisan queue:work
```

---

## Resultado QA

Pendiente — lo registra el Coordinador cuando QA valide el `final_sha`.

Para QA: el entorno se valida ejecutándolo, no leyéndolo. Dos avisos concretos
para que no se pierda tiempo en falsos negativos ni en falsos positivos:

- **No verifiques UT-03 con `docker kill` ni `docker compose stop`.** Ninguna
  política de reinicio de Docker revive un contenedor detenido desde fuera:
  Docker lo trata como decisión deliberada de quien opera. Comprobado con
  `unless-stopped` y con `always`. Lo que corresponde probar es que el proceso
  muera por su cuenta dentro del contenedor
  (`docker compose exec trabajador sh -c 'kill -TERM 1'`), que es el caso real
  que ADR-0003 exige cubrir.
- **No des por bueno un 200.** Durante este sprint hubo una versión que
  respondía 200 y figuraba `healthy` sirviendo una advertencia de PHP en vez de
  la aplicación. Comprueba el contenido.

## Pendientes o desviaciones

1. **UT-04 no está cerrada dentro de esta rama.** El texto está redactado arriba,
   pero `README.md` no es ruta de `devops`. Acordado con el Coordinador: él lo
   aplica en una rama de gobernanza, la integra a `develop` y me pasa el SHA;
   yo traigo `develop` a esta rama **antes** de fijar el `final_sha`, para que
   la rama contenga el compose y el README juntos y el criterio de UT-04 —
   levantar todo siguiendo solo el README — se pueda verificar sin pasos
   intermedios.
2. **Endurecimiento de configuración: es de S-DO-02, no de este sprint.**
   `APP_DEBUG=true` y `APP_ENV=local` son lo correcto en un entorno local de
   desarrollo; forzarlos aquí empeoraría el entorno sin proteger nada.
   `SESSION_SECURE_COOKIE` exige HTTPS y `SESSION_ENCRYPT` cobra sentido con
   sesiones reales de usuarios: las dos condiciones aparecen con el entorno
   servido, que este RFC declara fuera de alcance. El Coordinador ya lo registró
   como entrada obligatoria de S-DO-02 en el estado global.
3. **La suite `Feature` necesita que le pasen la base de pruebas dentro del
   contenedor.** No es un defecto de este sprint: PHPUnit no pisa una variable
   de entorno ya presente si el `<env>` no lleva `force="true"`, y el contenedor
   define `DB_DATABASE` como variable real. La salvaguarda de S-00 lo detecta y
   aborta, así que es ruidoso pero no peligroso. Queda documentado en el README.
   Si se quisiera que `php artisan test` funcione sin el añadido, habría que
   poner `force="true"` en `phpunit.xml`, que es ruta de `implementation-backend`
   y decisión de Arquitectura, no mía.
4. **Un `.env` con `DB_URL` deja el servicio `app` reiniciándose en bucle.** La
   guardia lo detiene con un mensaje claro en los logs, pero la política de
   reinicio vuelve a intentarlo. Es ruidoso; se prefirió eso a arrancar con una
   conexión equivocada en silencio.
5. **La imagen no es un artefacto de despliegue.** No contiene el código —el
   proyecto se monta— y trae herramientas de desarrollo. S-DO-02 necesita
   construir su propia imagen autocontenida; esta no se promueve.
6. **Sin integración continua todavía.** La verificación de este sprint fue
   local, como manda `AGENTS.md` hasta que S-DO-02 exista. Cuando ese sprint
   escriba el workflow, tendrá que disparar sobre `develop` además de `main`.
