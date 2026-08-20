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
     * @param  array<string, CodigoDeError>  $codigosPorCampo  código específico de negocio para ese campo
     * @return array<string, mixed>
     */
    public static function validar(array $campos, array $reglas, array $codigosPorCampo = []): array
    {
        $validador = Validator::make($campos, $reglas);

        if ($validador->fails()) {
            $campo = (string) array_key_first($validador->failed());
            $reglasFalladas = array_keys($validador->failed()[$campo]);

            throw new ErrorDeDominio(
                $codigosPorCampo[$campo] ?? self::codigoSegunRegla($reglasFalladas),
                (string) $validador->errors()->first(),
                ['campo' => $campo]
            );
        }

        return $validador->validated();
    }

    /** @param  array<int, string>  $reglasFalladas */
    private static function codigoSegunRegla(array $reglasFalladas): CodigoDeError
    {
        foreach ($reglasFalladas as $regla) {
            $codigo = match ($regla) {
                'Required' => CodigoDeError::CAMPO_REQUERIDO,
                'Unique' => CodigoDeError::DOCUMENTO_DUPLICADO,
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
