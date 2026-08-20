<?php

namespace App\Compartido\Documentos;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;

/**
 * Comprueba que un número de documento corresponda a su tipo.
 *
 * Vive en Compartido porque proveedores y clientes validan lo mismo con
 * reglas distintas por tipo, y porque el mismo número acaba viajando al
 * comprobante: un DNI de siete dígitos aceptado acá es un comprobante que
 * SUNAT rechaza más tarde, cuando ya se cobró la venta.
 */
final class ValidadorDeDocumento
{
    public static function tipoDesde(mixed $valor): TipoDeDocumento
    {
        $tipo = is_string($valor) || is_int($valor)
            ? TipoDeDocumento::tryFrom((string) $valor)
            : null;

        if ($tipo === null) {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_FORMATO_INVALIDO,
                'El tipo de documento no es uno de los admitidos.',
                ['campo' => 'tipo_documento']
            );
        }

        return $tipo;
    }

    /**
     * Devuelve el número tal como debe guardarse: recortado, o null cuando el
     * tipo no lleva documento. Nunca corrige el contenido.
     */
    public static function numeroValidado(TipoDeDocumento $tipo, mixed $numero): ?string
    {
        $numero = is_string($numero) ? trim($numero) : $numero;

        if (! $tipo->exigeNumero()) {
            if ($numero !== null && $numero !== '') {
                throw self::invalido("Un cliente {$tipo->descripcion()} no lleva número de documento.");
            }

            return null;
        }

        if (! is_string($numero) || $numero === '') {
            throw new ErrorDeDominio(
                CodigoDeError::CAMPO_REQUERIDO,
                "Falta el número de {$tipo->descripcion()}.",
                ['campo' => 'numero_documento']
            );
        }

        if (preg_match((string) $tipo->patron(), $numero) !== 1) {
            throw self::invalido("El número de {$tipo->descripcion()} no tiene el formato esperado.");
        }

        return $numero;
    }

    private static function invalido(string $mensaje): ErrorDeDominio
    {
        return new ErrorDeDominio(
            CodigoDeError::DOCUMENTO_INVALIDO,
            $mensaje,
            ['campo' => 'numero_documento']
        );
    }
}
