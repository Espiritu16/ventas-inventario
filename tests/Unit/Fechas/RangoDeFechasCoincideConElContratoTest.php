<?php

namespace Tests\Unit\Fechas;

use App\Compartido\Fechas\RangoDeFechas;
use PHPUnit\Framework\TestCase;

/**
 * El tope de amplitud vivía escrito a mano en dos servicios y valía 366 en los
 * dos; cambiar uno solo dejaba la suite entera en verde. Esa mitad se cerró
 * eliminando la duplicación: hoy `RangoDeFechas::MAXIMO_DIAS` es la única
 * fuente en código.
 *
 * Queda la otra mitad, la que no se puede eliminar: el número también está
 * escrito en `docs/contratos/inventario.md`, que es un documento que la gente
 * lee y donde el dato nace —Arquitectura es su autoridad, no este código—.
 * Para esa clase de duplicación el proyecto ya fijó el segundo mejor cierre:
 * una prueba que falla cuando divergen.
 *
 * La dirección importa: el contrato manda y el código sigue. Por eso se lee el
 * documento en vez de copiar su número acá, que sería volver a tener dos
 * valores que alguien debe mantener iguales.
 */
final class RangoDeFechasCoincideConElContratoTest extends TestCase
{
    private const CONTRATO = __DIR__.'/../../../docs/contratos/inventario.md';

    public function test_el_tope_es_el_que_declara_el_contrato_del_kardex(): void
    {
        $documento = (string) file_get_contents(self::CONTRATO);

        $encontrado = preg_match('/rango máximo de (\d+) días/u', $documento, $coincidencia);

        $this->assertSame(
            1,
            $encontrado,
            'El contrato ya no dice «rango máximo de N días» en GET /inventario/kardex. '
            .'Si cambió de forma, esta prueba dejaría de comparar nada y quedaría verde '
            .'sin comprobar: hay que actualizarla, no borrarla.'
        );

        $this->assertSame(
            (int) $coincidencia[1],
            RangoDeFechas::MAXIMO_DIAS,
            'El tope del código y el del contrato se separaron. El contrato manda: '
            .'si el número tiene que cambiar, lo cambia Arquitectura primero.'
        );
    }
}
