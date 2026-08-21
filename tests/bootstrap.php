<?php

use Tests\EntornoDePruebas;

/**
 * Bootstrap de la suite: decide contra qué base corren las pruebas antes de
 * que exista una conexión que pueda equivocarse de base.
 *
 * Sigue cargando el autoload de Composer, que es lo que `phpunit.xml` apuntaba
 * directamente hasta ahora.
 */
require __DIR__.'/../vendor/autoload.php';

EntornoDePruebas::preparar(dirname(__DIR__));
