<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ninguna prueba corre contra la base de la aplicación.
     *
     * `RefreshDatabase` ejecuta `migrate:fresh`, así que una suite mal
     * apuntada no falla: borra la base entera y sigue en verde. Por eso la
     * comprobación va aquí, apenas existe la configuración y antes de que los
     * traits toquen nada.
     *
     * El nombre se le pregunta al motor, no a la configuración. Laravel
     * resuelve `DB_URL` con prioridad sobre `DB_DATABASE`, de modo que el
     * nombre configurado y aquel al que el driver acaba conectándose pueden
     * ser distintos: mirar la configuración dejaría pasar exactamente el caso
     * que esta salvaguarda existe para impedir.
     *
     * Es la segunda de las dos guardas. La primera está en `tests/bootstrap.php`
     * y valida el nombre derivado antes de que exista conexión alguna; esta
     * comprueba la conexión real, que es lo único capaz de delatar un `DB_URL`
     * pisando el nombre. Se exige la convención completa
     * `ventas_inventario_<carril>_test` y no solo el sufijo: un nombre que
     * termine en `_test` pero pertenezca a otro proyecto del mismo clúster
     * también es una base ajena que `migrate:fresh` borraría.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $conexion = DB::connection();
        $efectiva = (string) $conexion->scalar('select current_database()');

        if (str_starts_with($efectiva, EntornoDePruebas::PREFIJO)
            && str_ends_with($efectiva, EntornoDePruebas::SUFIJO)) {
            return;
        }

        // Se compara contra el valor declarado en la configuración, no contra
        // el que ya resolvió el driver: es el que alguien leería en
        // phpunit.xml, y la divergencia entre ambos es justo la pista útil.
        $configurada = (string) config("database.connections.{$conexion->getName()}.database");
        $detalle = $efectiva === $configurada
            ? ''
            : " La configuración declara «{$configurada}», así que algo la está pisando: revisa DB_URL.";

        throw new RuntimeException(
            "Las pruebas están conectadas a «{$efectiva}», que no es una base de pruebas."
            .$detalle
            .' El nombre de la base de pruebas debe seguir la convención «'
            .EntornoDePruebas::PREFIJO.'<carril>'.EntornoDePruebas::SUFIJO.'».'
        );
    }
}
