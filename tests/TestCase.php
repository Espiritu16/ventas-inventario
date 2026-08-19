<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ninguna prueba corre contra la base de la aplicación.
     *
     * `RefreshDatabase` ejecuta `migrate:fresh`, así que una suite mal
     * apuntada no falla: borra la base buena entera y sigue en verde. Por eso
     * la comprobación va aquí, apenas existe la configuración y antes de que
     * los traits toquen nada, y no en una aserción dentro de una prueba.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $base = (string) config('database.connections.pgsql.database');

        if (! str_ends_with($base, '_test')) {
            throw new RuntimeException(
                "Las pruebas están apuntando a «{$base}», que no es una base de pruebas. "
                .'Revisa DB_DATABASE en phpunit.xml: debe terminar en _test.'
            );
        }
    }
}
