# ventas-inventario

Sistema de ventas e inventario para una comercializadora: controla mercadería
por lotes con fecha de vencimiento, registra ventas descontando primero lo que
vence antes, y emite boletas y facturas electrónicas directamente ante SUNAT.

## Stack
- PHP 8.5.9 + Laravel v13.26.1
- Blade + Livewire + Tailwind CSS 4.3.3, compilados con Vite 8.2.1
- PostgreSQL 18.3 — ver [ADR-0001](docs/decisiones/0001-motor-de-base-de-datos.md)
- Greenter para la emisión electrónica — ver [ADR-0002](docs/decisiones/0002-emision-electronica-propia.md)
- Gestor de paquetes: Composer 2.10.2 (PHP) y pnpm 11.22.0 sobre Node 24.19.0 (assets)

Las versiones quedaron verificadas al fundar el proyecto en S-00, contra la
fuente oficial y `composer.lock`.

## Cómo correrlo

Hay dos formas. **Con Docker** no necesitas instalar nada más que Docker, y es
la recomendada. **Sin Docker** necesitas PHP, PostgreSQL, Composer y pnpm
instalados en tu máquina.

### Con Docker (recomendado)

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

### Sin Docker

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

## Comandos
- Lint: `./vendor/bin/pint --test`
- Tests: `php artisan test`
- Build de assets: `pnpm build`
- Migraciones: `php artisan migrate`

## Estructura

Organización domain-first: cada dominio de negocio vive completo en
`app/Dominios/<Dominio>/`. Ver la skill `laravel-estructura`.

## Documentación técnica
- Requisitos: [docs/requisitos/](docs/requisitos/)
- Contratos internos: [docs/contratos/](docs/contratos/); experiencia e integración de la interfaz: [docs/frontend/](docs/frontend/)
- Modelo de datos: [docs/persistencia/modelo.md](docs/persistencia/modelo.md)
- Manejo de errores: [docs/errores/manejo-errores.md](docs/errores/manejo-errores.md)
- Integración con SUNAT: [docs/integraciones/sunat.md](docs/integraciones/sunat.md)
- Decisiones de arquitectura: [docs/decisiones/](docs/decisiones/)
- Roadmap y sprints: [docs/estado-global.md](docs/estado-global.md) y [docs/rfcs/](docs/rfcs/)

## Advertencia sobre SUNAT

Todo el desarrollo y toda la validación ocurren contra el **ambiente beta** de
SUNAT. Enviar comprobantes al ambiente de producción o usar el certificado
digital real exige autorización explícita del dueño del negocio, según
[AGENTS.md](AGENTS.md).

## Convenciones y gobernanza
Ver [AGENTS.md](AGENTS.md).
