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
| UT-02 | Servicio `db` sobre `postgres:18.3-alpine` con volumen propio, sin publicar puerto, y con el rol de la aplicación y sus dos bases creados al inicializarse | verificado |
| UT-03 | Servicio `trabajador` con `queue:work`, reintentos de espera creciente y reinicio automático ante caída del proceso | verificado |
| UT-04 | Sección "Cómo correrlo" del README, redactada aquí y aplicada por el Coordinador en `develop@12ba572`, ya fusionada a esta rama | verificado |

### Archivos

- `Dockerfile` — imagen de desarrollo de la aplicación.
- `docker-compose.yml` — los tres servicios, su red, sus volúmenes y su configuración.
- `docker/entrypoint.sh` — preparación del contenedor: dependencias, clave, assets y migraciones.
- `docker/postgres/10-crear-rol-y-bases.sh` — crea el rol de la aplicación (sin privilegios de superusuario) y sus dos bases al inicializar el volumen.
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
5. **Las credenciales de la base del contenedor son literales.** Son dos —la
   del superusuario que administra el clúster y la del rol de la aplicación—,
   locales y desechables: el puerto no se publica y el clúster se recrea con
   `docker compose down -v`. Arquitectura fijó su posición — no viola RNF-014 —
   con la condición de que el compose de un entorno servido no herede el patrón.
   Está advertido en el propio archivo y en el README.
6. **`APP_KEY` no se versiona.** Se genera en el primer arranque y queda en el
   `.env`, que está en `.gitignore`.
7. **La aplicación no se conecta como superusuario.** Un superusuario de
   PostgreSQL se salta toda comprobación de privilegios, y la bitácora de
   auditoría es de solo agregado precisamente porque MIG-009 le revoca `update`
   y `delete` al rol de la aplicación (RNF-004). Con un superusuario el entorno
   funciona igual y la garantía no existe, sin que nada lo indique. El rol de la
   aplicación lleva `CREATEDB` para las bases por carril de la ola 3.
8. **El comando documentado recrea los contenedores siempre.** No es
   conservadurismo: con el entorno ya levantado, `docker compose up -d` no
   recrea nada y el entrypoint no vuelve a correr, así que el entorno se queda
   con las dependencias y los assets de antes del cambio de rama.
9. **El compose no fija un nombre de proyecto.** Con un nombre fijo, dos copias
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

### 2026-08-19 — cierre: `develop` fusionado y UT-04 verificada sobre un clon

El Coordinador aplicó el texto del README en `develop@12ba572` y se fusionó a
esta rama en `ac207cd`. Con eso UT-04 dejó de depender de un paso externo y su
criterio se pudo comprobar tal como está escrito: **alguien clona el repositorio,
sigue solo el README y levanta todo**.

Se verificó así, y no leyendo el texto: se clonó `sprint/S-DO-01` en un
directorio vacío, se ejecutó el único comando que el README pide y se recorrió
cada promesa del documento contra el clon. Todas se cumplieron. El clon y sus
volúmenes se eliminaron después.

Antes de fusionar se revisó qué traía `develop`: entre otras cosas cambió
`AGENTS.md`, pero el cambio asigna `routes/backend.php` a
`implementation-backend` y no toca las rutas ni los límites del rol `devops`.

### 2026-08-19 — revalidación tras el RECHAZO de QA

QA rechazó con razón y el defecto era de fondo, no de forma: el entrypoint
instalaba dependencias y compilaba assets **solo cuando faltaban**, así que traer
código nuevo dejaba el entorno corriendo con lo anterior. Un entorno cuyo
resultado depende de cuándo se levantó por primera vez no es reproducible, que
es literalmente lo que este sprint entrega.

**Corrección:** `composer install` y `pnpm install`/`pnpm build` corren siempre.
Son idempotentes y cuestan segundos cuando no hay nada que cambiar. Se descartó
condicionar por hash del lockfile: agregaría maquinaria para ahorrar segundos, y
el modo de fallo que evita es justamente el que acaba de costar un rechazo.

Reproducido y verificado en el escenario real, no deducido: se levantó el
entorno con el código anterior a S-01-B (sin Livewire en `composer.json`), se
trajo `develop`, y al volver a levantar el entorno se puso al día solo.

**Al correr por primera vez la suite real del proyecto dentro del contenedor
aparecieron dos defectos más, ninguno visible antes** porque la rama vieja solo
tenía 7 pruebas:

1. **17 pruebas fallaban con 419.** `APP_ENV` y `QUEUE_CONNECTION` estaban
   definidas como variables de entorno reales en el compose, y una variable ya
   presente gana sobre el bloque `<env>` de `phpunit.xml` salvo que lleve
   `force="true"`. Sin `APP_ENV=testing`, Laravel no omite la verificación CSRF.
   Con `QUEUE_CONNECTION` el efecto era silencioso: las pruebas encolaban de
   verdad en vez de ejecutar en el acto. Es el mismo mecanismo que ya conocíamos
   por `DB_DATABASE`, aplicado a dos variables que no habíamos mirado. Ambas
   salen ahora del `.env`. La regla que queda escrita en el compose: **este
   archivo define dónde está el servidor de base de datos; `phpunit.xml` define
   cuál base y en qué modo corre la aplicación.**
2. **La bitácora de auditoría no estaba protegida.** El rol con el que la
   aplicación se conectaba era el superusuario del clúster, y un superusuario de
   PostgreSQL se salta toda comprobación de privilegios: la revocación de
   `update` y `delete` sobre `auditorias` que impone MIG-009 (RNF-004) no tenía
   ningún efecto. El entorno funcionaba y la garantía simplemente no existía.
   Ahora el superusuario solo administra el clúster y la aplicación usa un rol
   propio, sin superusuario y con `CREATEDB` para las bases por carril de la
   ola 3.

### 2026-08-19 — el escenario de cambio de rama, y un hueco que dejó al descubierto

QA anticipó que al revalidar iba a cambiar de rama y volver a levantar. Se probó
antes, y **encontró algo que la corrección anterior no cubría**: con el entorno
ya levantado, `docker compose up -d` no recrea nada, así que el entrypoint no
vuelve a ejecutarse y la sincronización no ocurre. Reproducido de las dos
formas: yendo a una rama con menos dependencias el entorno queda con restos
inofensivos; yendo a una que necesita más, **la aplicación responde 500**.

No se resuelve dentro del contenedor: el entrypoint corre al arrancar y nada
puede obligarlo a correr sin recrear. Lo que sí se hizo:

- **El comando documentado pasa a ser `docker compose up -d --build --force-recreate`**,
  para primera vez y para cada vez. Verificado: resincroniza en 20 s y deja la
  suite completa en verde. Es el mismo criterio de "converger siempre" que se
  aplicó al entrypoint, aplicado al paso humano.
- **El healthcheck baja de 12 reintentos a 3.** Medido: con 12, un entorno roto
  en caliente tardaba 105 s en aparecer como `unhealthy`; con 3 son ~30 s. Los
  fallos durante `start_period` no cuentan, así que no arriesga marcar por error
  un arranque lento. No sustituye al comando correcto, pero acorta el tiempo en
  que el entorno miente.

### 2026-08-19 — origen de los volúmenes huérfanos

Los tres eran míos y están eliminados. `ventas-inventario_datos_postgres` y
`ventas-inventario_node_modules` quedaron de cuando el compose fijaba
`name: ventas-inventario`, que se quitó en `939973e`. QA verificó que
`ventas-inventario_claves` no aparece en ninguna versión commiteada del archivo,
y tiene razón: **lo creó un borrador del compose que probé y descarté antes de
commitear** — es la primera versión del manejo de `APP_KEY` que está descrita
como hallazgo 5 más arriba, la que guardaba la clave en un volumen propio en vez
de en el `.env`. Contenía únicamente una `APP_KEY` generada para un entorno
local desechable; ninguna credencial real pasó nunca por ahí.

## Evidencia de verificación

Todo sobre `ventas-inventario@44e76c2`, con `develop@0e6a0ac` ya fusionado —así
que **la suite es la real del proyecto, 89 pruebas, no las 7 de la entrega
anterior**. Partiendo de un árbol sin `vendor/`, sin `node_modules/`, sin
`public/build/` y sin `.env`, y con `docker compose down -v` previo.

| Qué se comprobó | Cómo | Resultado |
|---|---|---|
| Un solo comando levanta todo | `docker compose up -d --build` desde cero | los tres servicios arriba en **33 s** (con las imágenes base ya descargadas; la primera vez tarda más) |
| UT-01 — la imagen construye y responde | `docker compose exec app php artisan --version` | `Laravel Framework 13.26.1` sobre PHP 8.5.9 |
| UT-01 — extensiones de la firma y del motor | `php -m` dentro del contenedor | `bcmath dom intl openssl pcntl pdo_pgsql pgsql soap zip` |
| La raíz sirve la aplicación, no una advertencia | `curl http://127.0.0.1:8080/` | 200, con el HTML del layout y la referencia a `build/assets/app-*` |
| La aplicación habla con la base del contenedor | `select current_database(), inet_server_addr()` | `ventas_inventario @ 172.24.0.2` — la red interna, no el host |
| UT-02 — el dato sobrevive | insertar, `docker compose down`, `up`, leer | el dato vuelve a leerse tras destruir y recrear los contenedores |
| UT-02 — base de pruebas creada | `pg_database` del contenedor | `ventas_inventario`, `ventas_inventario_test` |
| UT-03 — el trabajador procesa | encolar un trabajo y leer el log | `DONE`, y la línea aparece en `laravel.log` |
| UT-03 — vuelve solo tras caerse | matar el proceso dentro del contenedor | PID 70419 → 70838, `RestartCount` 1, `running`; y vuelve a procesar (1 → 2 trabajos) |
| `./vendor/bin/pint --test` | dentro del contenedor | PASA — 52 archivos |
| `php artisan test --testsuite=Unit` | dentro del contenedor | 4 passed (4 aserciones) |
| `php artisan test --testsuite=Feature` | dentro del contenedor, con `-e DB_DATABASE=ventas_inventario_test` | **89 passed (184 aserciones)** — la suite completa con S-01-B |
| El rol de la aplicación no es superusuario | `select rolsuper` con el usuario de la conexión | `no` — la revocación de MIG-009 sobre la bitácora se cumple, y las 2 pruebas que la comprueban pasan |
| **QA-01 — el entorno se pone al día con código nuevo** | levantar sin S-01-B, traer `develop`, volver a levantar | instala Livewire solo; raíz 200 y suite completa en verde |
| **QA-01 — cambio de rama con dependencias distintas** | levantar en una rama, cambiar a otra, volver a levantar | con `up -d` a secas queda desincronizado (**500**, reproducido); con `up -d --build --force-recreate` resincroniza en 20 s y la suite queda en verde |
| El healthcheck detecta un entorno roto en caliente | dejarlo desincronizado y observar `docker inspect` | pasa a `unhealthy`; con `retries: 12` tardaba 105 s medidos, con `retries: 3` ~30 s |
| `pnpm build` | dentro del contenedor | PASA |
| La base de pruebas queda sana tras la suite | `select count(*) from migrations` | 5 migraciones, 10 tablas |
| La guardia de `DB_URL` funciona | agregar `DB_URL=` al `.env` y levantar | el arranque se detiene con el mensaje que nombra la causa |
| Nada del host se tocó | `docker compose ps`, puerto 5432 del host | ningún servicio publica 5432; la instalación local sigue escuchando |
| El repositorio queda limpio | `git status --porcelain` | solo los archivos nuevos del sprint |
| `docker compose down -v` deja la máquina limpia | al desmontar el entorno | contenedores, red y los dos volúmenes eliminados |

### UT-04: qué falta reverificar

La prueba del clon —clonar la rama en un directorio vacío y seguir solo el
README— se hizo sobre la entrega anterior (`ac207cd`) y pasó. **No se rehízo
sobre este SHA a propósito**, porque el README todavía documenta
`docker compose up -d` como comando único y esa es justamente una de las cosas
que hay que corregir: con el entorno ya levantado, ese comando no resincroniza.

El texto corregido está abajo. En cuanto el Coordinador lo aplique y lo integre,
traigo `develop` otra vez y rehago la prueba del clon completa sobre el SHA
final. Dar UT-04 por verificada sobre este SHA sin eso sería exactamente el tipo
de afirmación sin respaldo que este sprint viene corrigiendo.

Ninguna credencial real pasó por este sprint. Las únicas contraseñas que
aparecen son las dos del contenedor de base de datos —la del superusuario que
solo administra el clúster y la del rol de la aplicación—, ambas locales y
desechables, en `docker-compose.yml` y en el script de inicialización, con la
advertencia de que un entorno servido no puede heredar ese patrón.

## Correcciones pendientes del README, para el Coordinador

El README vive en `develop` y no es ruta de este rol. Estos son los tres cambios
que la revalidación dejó pendientes. Los dos primeros los pidió QA; el tercero
salió de probar el escenario de cambio de rama y es el más importante de los
tres.

**1. El comando principal. Reemplazar el bloque de "Con Docker".** Donde hoy
dice `docker compose up -d`, poner:

```bash
docker compose up -d --build --force-recreate
```

Y a continuación, en lugar de la frase "Eso es todo…", este texto:

> Ese comando construye la imagen, levanta PostgreSQL, instala las dependencias
> de PHP y de Node, genera la clave de la aplicación, compila los assets, aplica
> las migraciones y arranca el servidor y el proceso trabajador de la cola. La
> primera vez tarda varios minutos porque descarga las imágenes y compila las
> extensiones de PHP; las siguientes son cuestión de segundos.
>
> **Usa ese mismo comando cada vez**, no solo la primera: después de un `git
> pull`, después de cambiar de rama, siempre. Es lo que mantiene el entorno
> sincronizado con el código que tienes delante.
>
> `docker compose up -d` a secas sirve para arrancar un entorno que estaba
> apagado, pero **si ya está corriendo no hace nada**: Docker ve los
> contenedores levantados y los deja como están, así que las dependencias y los
> assets se quedan como estaban antes de que cambiaras de rama. Si el código
> nuevo necesita algo que el entorno viejo no tiene, la aplicación responde 500;
> si solo cambiaron los estilos, la página carga con los de antes y nada avisa.
> Por eso el comando de arriba lleva `--force-recreate`.

**2. La guardia de `DB_URL`.** Donde dice "el contenedor se detiene y te lo
dice", reemplazar por:

> La única excepción es `DB_URL`: si la tienes definida con un valor, el
> contenedor no arranca y lo dice en sus registros (`docker compose logs app`),
> porque esa variable tiene prioridad sobre todas las demás y te conectaría a
> otro sitio sin avisar. Verás el servicio reiniciándose una y otra vez en
> `docker compose ps`, no detenido: quita esa línea del `.env` y volverá a
> levantar.

**3. La base de datos.** Agregar al final del párrafo "La base de datos":

> Dentro del contenedor hay dos roles: uno que administra el clúster y otro, sin
> privilegios de superusuario, con el que se conecta la aplicación. La
> separación no es decorativa: la bitácora de auditoría es de solo agregado
> porque la base le revoca `update` y `delete` al rol de la aplicación, y un
> superusuario se saltaría esa revocación sin que nada lo indicara.

El resto del texto sigue siendo correcto tal como está; se verificó comando por
comando contra el entorno actual, incluida la sesión `psql`.
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
- **Para el escenario de cambio de rama, el comando importa.** `docker compose
  up -d` sobre un entorno ya corriendo no sincroniza nada — está reproducido y
  documentado como límite conocido, no como defecto pendiente. El comando que
  corresponde, y el que el README pasa a documentar, es
  `docker compose up -d --build --force-recreate`. Si pruebas con el primero y
  encuentras un 500, es el comportamiento esperado y descrito, no un hallazgo
  nuevo.

## Pendientes o desviaciones

1. **Falta un paso del Coordinador antes de fijar el `final_sha`.** El README
   necesita las tres correcciones de arriba, y la más importante —el comando
   con `--force-recreate`— es parte de la corrección de QA-01, no un detalle de
   redacción: sin ella, el escenario que QA va a probar sigue rompiéndose desde
   el lado humano aunque el entrypoint ya converja. Cuando el Coordinador las
   aplique e integre, traigo `develop`, rehago la prueba del clon completa y
   entrego en EN_VALIDACION con el `final_sha` fijado. Por eso este handoff
   queda en EN_PROGRESO y con `final_sha: null`.
2. **Un `docker compose up -d` a secas sobre un entorno ya corriendo no
   sincroniza, y no hay forma de arreglarlo dentro del contenedor.** El
   entrypoint se ejecuta al arrancar; si Docker no recrea el contenedor, no hay
   nada que pueda dispararlo. Se mitiga por los dos lados: el comando
   documentado recrea siempre, y el healthcheck pasa a `unhealthy` en ~30 s
   cuando el entorno queda desincronizado. Queda como límite conocido, no como
   algo resuelto.
3. **Endurecimiento de configuración: es de S-DO-02, no de este sprint.**
   `APP_DEBUG=true` y `APP_ENV=local` son lo correcto en un entorno local de
   desarrollo; forzarlos aquí empeoraría el entorno sin proteger nada.
   `SESSION_SECURE_COOKIE` exige HTTPS y `SESSION_ENCRYPT` cobra sentido con
   sesiones reales de usuarios: las dos condiciones aparecen con el entorno
   servido, que este RFC declara fuera de alcance. El Coordinador ya lo registró
   como entrada obligatoria de S-DO-02 en el estado global.
4. **La suite `Feature` necesita que le pasen la base de pruebas dentro del
   contenedor.** No es un defecto de este sprint: PHPUnit no pisa una variable
   de entorno ya presente si el `<env>` no lleva `force="true"`, y el contenedor
   define `DB_DATABASE` como variable real. La salvaguarda de S-00 lo detecta y
   aborta, así que es ruidoso pero no peligroso. Queda documentado en el README.
   Se propuso poner `force="true"` en `phpunit.xml` para ahorrarse el añadido y
   **Arquitectura lo rechazó, con razón**: eso fijaría la base de pruebas a un
   único nombre para todo el proyecto, y desde la ola 3 cada carril paralelo usa
   la suya. Arreglaría la comodidad de un comando a costa del aislamiento de
   tres sprints. El `-e DB_DATABASE=...` explícito se queda.
5. **Un `.env` con `DB_URL` deja el servicio `app` reiniciándose en bucle.** La
   guardia lo detiene con un mensaje claro en los logs, pero la política de
   reinicio vuelve a intentarlo. Es ruidoso; se prefirió eso a arrancar con una
   conexión equivocada en silencio.
6. **La imagen no es un artefacto de despliegue.** No contiene el código —el
   proyecto se monta— y trae herramientas de desarrollo. S-DO-02 necesita
   construir su propia imagen autocontenida; esta no se promueve.
7. **Para la ola 3, el entorno contenerizado ya cubre el aislamiento por
   carril.** El estado global decidió que cada carril paralelo use su propia
   base de pruebas. Dentro de este entorno eso no necesita un GRANT: el usuario
   del contenedor es dueño de su propio clúster y puede crear y borrar bases
   —comprobado—, y además cada copia del repositorio levanta su propio Compose
   con volúmenes separados, así que dos carriles en contenedores ya no comparten
   base aunque usaran el mismo nombre. La decisión del estado global sigue
   valiendo para quien trabaje contra la instalación local.
8. **Sin integración continua todavía.** La verificación de este sprint fue
   local, como manda `AGENTS.md` hasta que S-DO-02 exista. Cuando ese sprint
   escriba el workflow, tendrá que disparar sobre `develop` además de `main`.
