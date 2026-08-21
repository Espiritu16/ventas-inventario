<?php

namespace Tests\Unit\Fundacion;

use PHPUnit\Framework\TestCase;
use Tests\EntornoDePruebas;

/**
 * El nombre de la base de pruebas se calcula, y de ese cálculo depende que dos
 * carriles no se borren la base entre sí. Acá se comprueba sin tocar PostgreSQL:
 * la derivación es una función pura de la ruta y del token de paralelismo.
 *
 * La convención completa se verifica, no solo el sufijo. Un nombre como
 * `ventas_inventario_test_s02b` —el orden que sale natural al escribirlo— lo
 * rechazaría la propia guarda de `Tests\TestCase`, y el carril se quedaría sin
 * poder correr sus pruebas.
 */
final class NombreDeBaseDePruebasTest extends TestCase
{
    public function test_el_nombre_derivado_respeta_la_convencion_del_proyecto(): void
    {
        $nombre = EntornoDePruebas::nombreDerivadoDe('/Users/alguien/ventas-inventario-carriles/s02b');

        $this->assertStringStartsWith('ventas_inventario_', $nombre);
        $this->assertStringEndsWith('_test', $nombre);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]{1,63}$/', $nombre);
    }

    public function test_el_nombre_nunca_es_el_de_la_base_de_desarrollo(): void
    {
        $nombre = EntornoDePruebas::nombreDerivadoDe('/Users/alguien/ventas-inventario');

        $this->assertNotSame('ventas_inventario', $nombre);
    }

    public function test_dos_arboles_de_trabajo_derivan_bases_distintas(): void
    {
        $uno = EntornoDePruebas::nombreDerivadoDe('/Users/alguien/ventas-inventario-carriles/s02b');
        $otro = EntornoDePruebas::nombreDerivadoDe('/Users/alguien/ventas-inventario-carriles/s03b');

        $this->assertNotSame($uno, $otro);
    }

    /**
     * Dos worktrees pueden llamarse igual en carpetas distintas: si el nombre
     * saliera solo del basename, compartirían base sin que nada lo delate.
     */
    public function test_dos_arboles_con_el_mismo_nombre_en_rutas_distintas_no_colisionan(): void
    {
        $uno = EntornoDePruebas::nombreDerivadoDe('/Users/alguien/carriles/s02b');
        $otro = EntornoDePruebas::nombreDerivadoDe('/Users/otro/carriles/s02b');

        $this->assertNotSame($uno, $otro);
    }

    /**
     * El caso que la ruta sola no cubre: en CI dos jobs paralelos comparten el
     * mismo checkout, y solo el token de `--parallel` los separa.
     */
    public function test_el_token_de_paralelismo_separa_procesos_en_la_misma_ruta(): void
    {
        $raiz = '/home/runner/work/ventas-inventario/ventas-inventario';

        $uno = EntornoDePruebas::nombreDerivadoDe($raiz, '1');
        $otro = EntornoDePruebas::nombreDerivadoDe($raiz, '2');

        $this->assertNotSame($uno, $otro);
        $this->assertMatchesRegularExpression('/^[a-z0-9_]{1,63}$/', $uno);
        $this->assertStringEndsWith('_test', $uno);
    }

    public function test_el_mismo_arbol_deriva_siempre_el_mismo_nombre(): void
    {
        $raiz = '/Users/alguien/ventas-inventario-carriles/s02b';

        $this->assertSame(
            EntornoDePruebas::nombreDerivadoDe($raiz),
            EntornoDePruebas::nombreDerivadoDe($raiz)
        );
    }

    /**
     * PostgreSQL trunca los identificadores a 63 bytes: un nombre más largo no
     * falla, se conecta a otra base en silencio.
     */
    public function test_una_ruta_larguisima_sigue_dando_un_nombre_que_postgresql_admite(): void
    {
        $raiz = '/Users/alguien/'.str_repeat('carril-de-nombre-interminable-', 8);

        $nombre = EntornoDePruebas::nombreDerivadoDe($raiz, str_repeat('token', 10));

        $this->assertLessThanOrEqual(63, strlen($nombre));
        $this->assertMatchesRegularExpression('/^[a-z0-9_]{1,63}$/', $nombre);
        $this->assertStringEndsWith('_test', $nombre);
    }
}
