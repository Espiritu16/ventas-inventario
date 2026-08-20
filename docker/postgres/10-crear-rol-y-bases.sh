#!/bin/sh
# Crea el rol de la aplicación y sus dos bases, la primera vez que el volumen de
# PostgreSQL se inicializa.
#
# Por qué el rol de la aplicación NO es el superusuario del contenedor, que
# sería lo más corto de escribir: un superusuario de PostgreSQL se salta todas
# las comprobaciones de privilegios. La bitácora de auditoría es de solo
# agregado porque MIG-009 revoca `update` y `delete` sobre esa tabla al rol de
# la aplicación (RNF-004) — y esa revocación no tiene ningún efecto sobre un
# superusuario. El entorno seguiría funcionando y la protección simplemente no
# existiría, sin que nada lo dijera.
#
# CREATEDB sí lo lleva: desde la ola 3 cada carril paralelo usa su propia base
# de pruebas, y tiene que poder crearla.

set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    CREATE ROLE ventas_inventario WITH LOGIN CREATEDB PASSWORD 'desarrollo_local';

    -- La base de la aplicación y la de pruebas. El nombre de la segunda termina
    -- en _test a propósito: la salvaguarda de tests/TestCase.php aborta la suite
    -- si la base a la que se conectó no termina así, y es lo que impide que una
    -- configuración equivocada borre la base de la aplicación.
    CREATE DATABASE ventas_inventario OWNER ventas_inventario;
    CREATE DATABASE ventas_inventario_test OWNER ventas_inventario;
EOSQL
