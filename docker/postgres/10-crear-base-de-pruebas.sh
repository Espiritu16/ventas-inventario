#!/bin/sh
# Crea la base de pruebas junto a la de la aplicación, la primera vez que el
# volumen de PostgreSQL se inicializa.
#
# El nombre termina en `_test` a propósito: la salvaguarda de tests/TestCase.php
# aborta la suite si la base a la que se conectó no termina así, y es lo que
# impide que una configuración equivocada borre la base de la aplicación.

set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE DATABASE ventas_inventario_test OWNER "$POSTGRES_USER";
EOSQL
