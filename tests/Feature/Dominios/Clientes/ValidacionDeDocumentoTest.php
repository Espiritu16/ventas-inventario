<?php

namespace Tests\Feature\Dominios\Clientes;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Documentos\ValidadorDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * La validación de documento es el corazón de este sprint, y su clase de
 * equivalencia es más ancha de lo que parece: además del contenido, están el
 * tipo, la longitud exacta por tipo, los separadores y la relación entre tener
 * documento y no tenerlo.
 *
 * Lo que se juega no es la comodidad de quien carga: un número mal aceptado
 * viaja al comprobante, y SUNAT lo rechaza después de que la venta ya se
 * cobró.
 */
final class ValidacionDeDocumentoTest extends TestCase
{
    public static function numerosValidos(): array
    {
        return [
            'DNI de 8 dígitos' => [TipoDeDocumento::DNI, '12345678', '12345678'],
            'DNI con espacios alrededor' => [TipoDeDocumento::DNI, '  12345678  ', '12345678'],
            'DNI que empieza en cero' => [TipoDeDocumento::DNI, '01234567', '01234567'],
            'RUC de 11 dígitos' => [TipoDeDocumento::RUC, '20123456789', '20123456789'],
            'carné alfanumérico' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA, 'AB123456', 'AB123456'],
            'carné de un carácter' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA, 'X', 'X'],
            'carné de doce' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA, 'ABCD12345678', 'ABCD12345678'],
        ];
    }

    #[DataProvider('numerosValidos')]
    public function test_acepta_el_numero_que_corresponde_al_tipo(
        TipoDeDocumento $tipo,
        string $entrada,
        string $esperado,
    ): void {
        $this->assertSame($esperado, ValidadorDeDocumento::numeroValidado($tipo, $entrada));
    }

    public static function numerosInvalidos(): array
    {
        return [
            'DNI de 7' => [TipoDeDocumento::DNI, '1234567'],
            'DNI de 9' => [TipoDeDocumento::DNI, '123456789'],
            'DNI con letras' => [TipoDeDocumento::DNI, '1234567A'],
            'DNI con guion' => [TipoDeDocumento::DNI, '1234-5678'],
            'DNI con punto' => [TipoDeDocumento::DNI, '12.345.678'],
            'DNI con espacio interno' => [TipoDeDocumento::DNI, '1234 5678'],
            'RUC de 10' => [TipoDeDocumento::RUC, '2012345678'],
            'RUC de 12' => [TipoDeDocumento::RUC, '201234567890'],
            'RUC con guiones' => [TipoDeDocumento::RUC, '20-12345678-9'],
            'carné de trece' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA, 'ABCD123456789'],
            'carné con guion' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA, 'AB-123456'],
            'solo espacios' => [TipoDeDocumento::DNI, '        '],
        ];
    }

    /**
     * Un documento con separadores se rechaza, no se limpia: guardar
     * "12345678" cuando alguien escribió "1234-5678" es guardar un número que
     * esa persona no verificó.
     */
    #[DataProvider('numerosInvalidos')]
    public function test_rechaza_el_numero_que_no_corresponde_al_tipo(TipoDeDocumento $tipo, string $entrada): void
    {
        try {
            ValidadorDeDocumento::numeroValidado($tipo, $entrada);
            $this->fail("Se aceptó «{$entrada}» como {$tipo->descripcion()}.");
        } catch (ErrorDeDominio $error) {
            $this->assertContains(
                $error->codigo,
                [CodigoDeError::DOCUMENTO_INVALIDO, CodigoDeError::CAMPO_REQUERIDO],
                "«{$entrada}» debe rechazarse por documento inválido o campo requerido."
            );
        }
    }

    public function test_sin_documento_el_numero_queda_nulo(): void
    {
        $this->assertNull(ValidadorDeDocumento::numeroValidado(TipoDeDocumento::SIN_DOCUMENTO, null));
        $this->assertNull(ValidadorDeDocumento::numeroValidado(TipoDeDocumento::SIN_DOCUMENTO, ''));
    }

    public function test_sin_documento_no_admite_un_numero(): void
    {
        try {
            ValidadorDeDocumento::numeroValidado(TipoDeDocumento::SIN_DOCUMENTO, '12345678');
            $this->fail('Se aceptó un número para un cliente sin documento.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_INVALIDO, $error->codigo);
        }
    }

    public static function tiposQueExigenNumero(): array
    {
        return [
            'DNI' => [TipoDeDocumento::DNI],
            'carné' => [TipoDeDocumento::CARNE_DE_EXTRANJERIA],
            'RUC' => [TipoDeDocumento::RUC],
        ];
    }

    /** Faltar no es lo mismo que estar mal: el código lo distingue. */
    #[DataProvider('tiposQueExigenNumero')]
    public function test_un_tipo_que_exige_numero_lo_reclama_como_campo_requerido(TipoDeDocumento $tipo): void
    {
        foreach ([null, ''] as $vacio) {
            try {
                ValidadorDeDocumento::numeroValidado($tipo, $vacio);
                $this->fail("Se aceptó un {$tipo->descripcion()} sin número.");
            } catch (ErrorDeDominio $error) {
                $this->assertSame(CodigoDeError::CAMPO_REQUERIDO, $error->codigo);
            }
        }
    }

    public static function tiposInvalidos(): array
    {
        return [
            'inexistente' => ['9'],
            'vacío' => [''],
            'nulo' => [null],
            'texto' => ['DNI'],
            'booleano' => [true],
            'arreglo' => [[]],
            'con espacios' => [' 1 '],
        ];
    }

    #[DataProvider('tiposInvalidos')]
    public function test_rechaza_un_tipo_de_documento_que_no_existe(mixed $tipo): void
    {
        try {
            ValidadorDeDocumento::tipoDesde($tipo);
            $this->fail('Se aceptó un tipo de documento que no está en el catálogo.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }
    }

    /** El entero 1 y la cadena "1" son el mismo tipo: llegan así según el origen. */
    public function test_acepta_el_tipo_como_entero_o_como_cadena(): void
    {
        $this->assertSame(TipoDeDocumento::DNI, ValidadorDeDocumento::tipoDesde('1'));
        $this->assertSame(TipoDeDocumento::DNI, ValidadorDeDocumento::tipoDesde(1));
    }
}
