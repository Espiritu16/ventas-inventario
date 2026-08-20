<?php

namespace App\Compartido\Errores;

use Illuminate\Support\Facades\Validator;

/**
 * Traduce un fallo de validación al código de la taxonomía que le
 * corresponde.
 *
 * Vive acá y no dentro de un servicio porque cada dominio valida lo suyo pero
 * todos deben rechazar con los mismos códigos: si cada uno los eligiera por su
 * cuenta, el mismo error terminaría con nombres distintos según la pantalla.
 */
final class ValidadorDeDominio
{
    /**
     * @param  array<string, mixed>  $campos
     * @param  array<string, array<int, mixed>>  $reglas
     * @return array<string, mixed>
     */
    public static function validar(array $campos, array $reglas): array
    {
        $validador = Validator::make($campos, $reglas);

        if ($validador->fails()) {
            $campo = (string) array_key_first($validador->failed());
            $reglasFalladas = array_keys($validador->failed()[$campo]);

            throw new ErrorDeDominio(
                self::codigoSegunRegla($reglasFalladas),
                (string) $validador->errors()->first(),
                ['campo' => $campo]
            );
        }

        return $validador->validated();
    }

    /**
     * Traduce las reglas que no dependen del dominio.
     *
     * **Acá solo se traduce lo que la regla dice por sí sola.** Un código que
     * nombra la entidad —`PRODUCTO_CODIGO_DUPLICADO`, `DOCUMENTO_DUPLICADO`—
     * no se puede elegir desde este punto, porque este validador recibe campos
     * sueltos y no sabe de qué entidad son. Esos rechazos van en el servicio
     * del dominio, con su comprobación propia, su mensaje y su campo: así lo
     * hacen usuarios, clientes, proveedores, categorías y productos.
     *
     * Hubo un parámetro para declarar un código por campo y se retiró: cubría
     * **cualquier** fallo de ese campo, no la regla que se quería nombrar, así
     * que un valor mal escrito respondía con el código del duplicado. Sus dos
     * únicos usos resultaron ser precisamente ese defecto.
     *
     * Un `unique` sin nada más cae al genérico de formato, que es visiblemente
     * incorrecto para un duplicado, y está fijado con una prueba: quien
     * agregue uno nuevo se encuentra con eso al escribirlo y no en
     * producción.
     *
     * @param  array<int, string>  $reglasFalladas
     */
    private static function codigoSegunRegla(array $reglasFalladas): CodigoDeError
    {
        foreach ($reglasFalladas as $regla) {
            $codigo = match ($regla) {
                'Required' => CodigoDeError::CAMPO_REQUERIDO,
                'Min', 'Max', 'Between' => CodigoDeError::CAMPO_FUERA_DE_RANGO,
                default => null,
            };

            if ($codigo !== null) {
                return $codigo;
            }
        }

        return CodigoDeError::CAMPO_FORMATO_INVALIDO;
    }
}
