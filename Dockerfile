# Imagen de la aplicación para el entorno local contenerizado (S-DO-01).
#
# Es una imagen de DESARROLLO: trae Composer, Node y pnpm porque el entorno
# local necesita instalar dependencias y compilar assets por su cuenta. La
# imagen de despliegue es otra cosa y la define S-DO-02, que es el sprint que
# tiene el despliegue en su alcance.
#
# El código de la aplicación no se copia acá: `docker-compose.yml` monta el
# repositorio dentro del contenedor, para que editar un archivo en el editor se
# vea sin reconstruir la imagen.

FROM php:8.5.9-cli

# Versiones fijadas a las mismas que S-00 verificó y dejó registradas en el
# README. Cambiarlas es una decisión, no un efecto secundario de reconstruir.
ARG VERSION_NODE=24.19.0
ARG VERSION_PNPM=11.22.0

# - libpq: PostgreSQL (ADR-0001)
# - libxml2 + libzip: firma del XML UBL 2.1 y su envío comprimido a SUNAT
# - libicu: intl, para formato de fechas y montos
# Los paquetes -dev se quedan en la imagen a propósito: es una imagen de
# desarrollo, y quitarlos solo ahorraría tamaño a cambio de romper la
# reconstrucción de una extensión más adelante.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        libicu-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        unzip \
        xz-utils; \
    rm -rf /var/lib/apt/lists/*

# pdo_pgsql/pgsql: motor de la aplicación.
# soap: los servicios de SUNAT son SOAP (docs/integraciones/sunat.md).
# zip: el comprobante viaja comprimido y el CDR vuelve comprimido.
# intl y bcmath: fechas con zona y aritmética exacta de importes (RNF-006, RNF-003).
#
# openssl, dom, simplexml, libxml y mbstring —lo que la firma del XML necesita—
# ya vienen compiladas en la imagen base, igual que OPcache. Agregar OPcache acá
# no es redundante sino un error: `docker-php-ext-install opcache` falla, porque
# no hay módulo compartido que instalar.
# pcntl: el trabajador de la cola la necesita para atender señales. Sin ella
# `queue:work` no se entera de un SIGTERM —se apaga de golpe en medio de un
# trabajo en vez de terminarlo— y tampoco puede aplicar el tiempo límite por
# trabajo. Se ve solo al apagar o al reiniciar el servicio, no al arrancarlo.
RUN docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pcntl \
        pdo_pgsql \
        pgsql \
        soap \
        zip

COPY --from=composer:2.10 /usr/bin/composer /usr/local/bin/composer

# Node se instala desde el binario oficial y no desde apt: apt trae una versión
# vieja, y la imagen `node` de Alpine es incompatible con esta base (musl vs glibc).
RUN set -eux; \
    arquitectura="$(dpkg --print-architecture)"; \
    case "$arquitectura" in \
        amd64) arquitectura_node='x64' ;; \
        arm64) arquitectura_node='arm64' ;; \
        *) echo "Arquitectura no soportada por esta imagen: $arquitectura" >&2; exit 1 ;; \
    esac; \
    curl -fsSL "https://nodejs.org/dist/v${VERSION_NODE}/node-v${VERSION_NODE}-linux-${arquitectura_node}.tar.xz" -o /tmp/node.tar.xz; \
    tar -xJf /tmp/node.tar.xz -C /usr/local --strip-components=1 --no-same-owner; \
    rm /tmp/node.tar.xz; \
    npm install -g "pnpm@${VERSION_PNPM}"; \
    npm cache clean --force; \
    node --version; \
    pnpm --version

# Usuario sin privilegios. El uid 1000 es el que Docker Desktop mapea al usuario
# del host en un montaje, así que los archivos que el contenedor escribe en el
# repositorio quedan editables desde fuera.
RUN groupadd --gid 1000 app \
    && useradd --uid 1000 --gid app --shell /bin/bash --create-home app

# node_modules recibe un volumen con nombre. Se crea acá, con su dueño ya
# puesto, porque Docker inicializa el volumen con el contenido y los permisos
# que encuentra en la imagen: si no existiera, el volumen quedaría de root y el
# usuario `app` no podría escribir en él.
RUN mkdir -p /app/node_modules \
    && chown -R app:app /app

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /app
USER app

ENTRYPOINT ["entrypoint"]

# El servidor se levanta con `php -S` y no con `php artisan serve`, y no es una
# preferencia de estilo. `artisan serve` vuelve a leer el .env y se lo inyecta al
# proceso servido, así que las variables que docker-compose.yml define quedan
# pisadas por el .env — pero solo para las peticiones web, no para la línea de
# comandos. El síntoma es de los peores: `php artisan tinker` conecta a la base
# del contenedor y responde bien, mientras el navegador falla contra 127.0.0.1.
#
# server.php es el enrutador que trae el propio framework: sirve el archivo
# estático si existe y manda el resto a index.php. Resuelve esas dos rutas
# contra `getcwd()`, así que el servidor tiene que arrancar parado en `public/`
# —no basta con `-t public`—. Si no, responde 200 con una advertencia de PHP en
# lugar de la página, que es peor que un error: parece que funciona.
CMD ["sh", "-c", "cd public && exec php -S 0.0.0.0:8000 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"]
