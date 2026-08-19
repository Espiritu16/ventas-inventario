#!/bin/bash
# Preparación del contenedor antes de ejecutar su comando (S-DO-01).
#
# Existe para que `docker compose up` sea de verdad un solo comando desde un
# clon recién hecho: sin esto haría falta instalar dependencias, generar la
# clave de la aplicación, compilar los assets y migrar a mano, que es
# exactamente el trámite manual que este sprint viene a eliminar.
#
# Solo el servicio `app` prepara el entorno (PREPARAR_ENTORNO=1). El trabajador
# de la cola arranca después de que `app` esté sano, así que se encuentra todo
# hecho y no compite por los mismos archivos.

set -euo pipefail

cd /app

registrar() {
    echo "[entrypoint] $*"
}

# --- Guardia: DB_URL en .env -------------------------------------------------
#
# Laravel resuelve `DB_URL` con prioridad sobre DB_HOST/DB_DATABASE, y el .env
# del repositorio se ve dentro del contenedor porque el proyecto está montado.
# Un DB_URL apuntando a la base del host haría que el contenedor ignore por
# completo su propio PostgreSQL sin decir nada. Es la misma divergencia entre
# configuración declarada y conexión real que QA encontró en S-00; acá se corta
# con un mensaje en vez de con un diagnóstico de media hora.
if [[ -f .env ]] && grep -Eq '^[[:space:]]*DB_URL=[^[:space:]]' .env; then
    cat >&2 <<'FIN'
[entrypoint] ERROR: tu archivo .env define DB_URL con un valor.

DB_URL tiene prioridad sobre DB_HOST y DB_DATABASE, así que el contenedor
terminaría conectándose a donde apunte esa variable en vez de a su propio
PostgreSQL, y lo haría en silencio.

Comenta o vacía esa línea en .env y vuelve a levantar el entorno. El .env solo
lo usa la instalación local; el entorno contenerizado toma su configuración de
docker-compose.yml.
FIN
    exit 1
fi

if [[ "${PREPARAR_ENTORNO:-0}" == "1" ]]; then
    # --- Dependencias de PHP -------------------------------------------------
    if [[ ! -f vendor/autoload.php ]]; then
        registrar "Instalando dependencias de Composer (primera vez, tarda un rato)."
        composer install --no-interaction --prefer-dist
    fi

    # --- Clave de la aplicación ----------------------------------------------
    #
    # Va al archivo .env y no a una variable exportada acá. La diferencia
    # importa: una variable exportada por este script solo la ve el proceso que
    # este script lanza, así que `docker compose exec app php artisan ...` —que
    # no pasa por el entrypoint— correría con otra clave. Eso rompe de verdad:
    # un trabajo encolado desde una sesión `exec` queda firmado con una clave
    # distinta a la del trabajador, y el trabajador lo rechaza por firma
    # inválida. En el .env la leen todos los procesos del contenedor por igual.
    #
    # El .env no se versiona (está en .gitignore) y se crea desde .env.example
    # solo si no existe: si ya tienes uno, se respeta tal cual y esta parte no
    # lo toca.
    if [[ ! -f .env ]]; then
        registrar "Creando .env desde .env.example."
        cp .env.example .env
    fi
    if ! grep -Eq '^APP_KEY=.+' .env; then
        registrar "Generando la clave de la aplicación."
        php artisan key:generate --no-interaction
    fi

    # --- Assets --------------------------------------------------------------
    #
    # Sin el manifiesto de Vite, cualquier vista que extienda el layout base
    # responde 500. En S-00 el README omitía este paso y QA terminó justamente
    # ahí, así que acá no es opcional ni queda a cargo de quien levanta el
    # entorno.
    #
    # --store-dir no es un detalle: pnpm guarda su almacén en el mismo sistema de
    # archivos que node_modules para poder enlazarlo en vez de copiarlo, y como
    # node_modules vive en un volumen, sin esto pnpm deduce que el sistema de
    # archivos correcto es el del proyecto montado y deja un `.pnpm-store` de
    # decenas de megas dentro del repositorio de quien programa.
    if [[ ! -f public/build/manifest.json ]]; then
        registrar "Instalando dependencias de Node y compilando los assets."
        pnpm install --frozen-lockfile --store-dir /app/node_modules/.pnpm-store
        pnpm build
    fi

    # --- Migraciones ---------------------------------------------------------
    #
    # La base del contenedor es propia y desechable, así que migrar al arrancar
    # es seguro. Esto no vale para un entorno servido: ahí las migraciones son
    # una operación con autorización, y eso lo define S-DO-02.
    registrar "Aplicando migraciones."
    php artisan migrate --force
fi

registrar "Listo. Ejecutando: $*"
exec "$@"
