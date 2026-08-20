<?php

namespace Tests\Unit\Arquitectura;

use PHPUnit\Framework\TestCase;
use Tests\Soporte\DetectorDeCamposValidados;

/**
 * El archivo de traducciones tiene que cubrir lo que los servicios validan de
 * verdad.
 *
 * Es la novena instancia del patrón de dos fuentes que este proyecto lleva
 * cerrando, y falla igual que las otras: no cuando se escribe, sino cuando una
 * de las dos cambia. Un campo nuevo sale en pantalla con su nombre técnico, o
 * una regla nueva sale sin traducir, y nada avisa.
 *
 * Los campos y las reglas se **leen del código**, no de una lista escrita al
 * lado. Comparar el mapa contra otra lista a mano no cerraría nada: movería el
 * problema a la lista nueva.
 */
final class TraduccionesCubrenLoQueSeValidaTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $traducciones;

    private string $app;

    protected function setUp(): void
    {
        parent::setUp();

        $raiz = dirname(__DIR__, 3);
        $this->app = $raiz.'/app';
        $this->traducciones = require $raiz.'/lang/es/validation.php';
    }

    public function test_cada_campo_validado_tiene_su_nombre_legible(): void
    {
        $sinNombre = array_values(array_diff(
            DetectorDeCamposValidados::campos($this->app),
            array_keys($this->traducciones['attributes'])
        ));

        $this->assertSame(
            [],
            $sinNombre,
            'Estos campos saldrían en pantalla con su identificador técnico: '.implode(', ', $sinNombre)
        );
    }

    /**
     * Y al revés: un nombre legible que ya no corresponde a ningún campo es
     * una entrada que quedó de algo que se renombró o se borró. No rompe nada
     * hoy, y por eso se acumula hasta que nadie sabe cuáles siguen vivas.
     */
    public function test_no_sobran_nombres_legibles(): void
    {
        $sobrantes = array_values(array_diff(
            array_keys($this->traducciones['attributes']),
            DetectorDeCamposValidados::campos($this->app)
        ));

        $this->assertSame(
            [],
            $sobrantes,
            'Estos nombres legibles ya no corresponden a ningún campo validado: '.implode(', ', $sobrantes)
        );
    }

    /**
     * Una regla sin traducción no falla: cae al idioma de reserva y devuelve
     * una frase creíble. Por eso hay que compararlas, y por eso el idioma de
     * reserva también es español — para que un hueco se vea como un hueco.
     */
    public function test_cada_regla_usada_tiene_su_mensaje_en_espanol(): void
    {
        $sinMensaje = array_values(array_filter(
            DetectorDeCamposValidados::reglas($this->app),
            fn (string $regla) => ! self::generaMensaje($regla) ? false : ! isset($this->traducciones[$regla])
        ));

        $this->assertSame(
            [],
            $sinMensaje,
            'Estas reglas mostrarían su mensaje sin traducir: '.implode(', ', $sinMensaje)
        );
    }

    /**
     * `nullable` y `sometimes` no rechazan nada por sí solas: dicen cuándo
     * mirar el resto, así que no tienen mensaje que traducir.
     */
    private static function generaMensaje(string $regla): bool
    {
        return ! in_array($regla, ['nullable', 'sometimes'], true);
    }
}
