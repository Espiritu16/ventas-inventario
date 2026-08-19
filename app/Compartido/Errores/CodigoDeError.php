<?php

namespace App\Compartido\Errores;

/**
 * Códigos de la taxonomía de docs/errores/manejo-errores.md.
 *
 * El valor es estable y es lo que las pruebas verifican; el texto que ve la
 * persona puede cambiar sin tocar el contrato, el código no.
 *
 * Solo están los que el sistema ya produce. Cada sprint agrega los suyos.
 */
enum CodigoDeError: string
{
    case NO_AUTENTICADO = 'NO_AUTENTICADO';
    case NO_AUTORIZADO = 'NO_AUTORIZADO';
    case CREDENCIALES_INVALIDAS = 'CREDENCIALES_INVALIDAS';
    case CAMPO_REQUERIDO = 'CAMPO_REQUERIDO';
    case CAMPO_FORMATO_INVALIDO = 'CAMPO_FORMATO_INVALIDO';
    case CAMPO_FUERA_DE_RANGO = 'CAMPO_FUERA_DE_RANGO';
    case RECURSO_NO_ENCONTRADO = 'RECURSO_NO_ENCONTRADO';
    case DOCUMENTO_DUPLICADO = 'DOCUMENTO_DUPLICADO';

    public function status(): int
    {
        return match ($this) {
            self::NO_AUTENTICADO => 401,
            self::NO_AUTORIZADO => 403,
            self::RECURSO_NO_ENCONTRADO => 404,
            self::DOCUMENTO_DUPLICADO => 409,
            self::CREDENCIALES_INVALIDAS,
            self::CAMPO_REQUERIDO,
            self::CAMPO_FORMATO_INVALIDO,
            self::CAMPO_FUERA_DE_RANGO => 422,
        };
    }
}
