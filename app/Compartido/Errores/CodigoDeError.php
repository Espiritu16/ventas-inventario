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
    case DOCUMENTO_INVALIDO = 'DOCUMENTO_INVALIDO';
    case PRODUCTO_CODIGO_DUPLICADO = 'PRODUCTO_CODIGO_DUPLICADO';
    case CATEGORIA_NOMBRE_DUPLICADO = 'CATEGORIA_NOMBRE_DUPLICADO';
    case PRODUCTO_PRECIO_MAYOR_INVALIDO = 'PRODUCTO_PRECIO_MAYOR_INVALIDO';
    case PRODUCTO_INACTIVO = 'PRODUCTO_INACTIVO';
    case LOTE_VENCIMIENTO_PASADO = 'LOTE_VENCIMIENTO_PASADO';
    case AJUSTE_SIN_MOTIVO = 'AJUSTE_SIN_MOTIVO';
    case AJUSTE_CANTIDAD_NEGATIVA = 'AJUSTE_CANTIDAD_NEGATIVA';
    case COMPRA_SIN_LINEAS = 'COMPRA_SIN_LINEAS';
    case COMPRA_DOCUMENTO_DUPLICADO = 'COMPRA_DOCUMENTO_DUPLICADO';
    case STOCK_INSUFICIENTE = 'STOCK_INSUFICIENTE';
    case VENTA_SIN_LINEAS = 'VENTA_SIN_LINEAS';
    case TIPO_PRECIO_INVALIDO = 'TIPO_PRECIO_INVALIDO';
    case LOTE_VENCIDO = 'LOTE_VENCIDO';
    case FACTURA_REQUIERE_RUC = 'FACTURA_REQUIERE_RUC';
    case BOLETA_REQUIERE_DOCUMENTO = 'BOLETA_REQUIERE_DOCUMENTO';
    case SERIE_NO_CONFIGURADA = 'SERIE_NO_CONFIGURADA';
    case CORRELATIVO_EN_CONFLICTO = 'CORRELATIVO_EN_CONFLICTO';
    case OPERACION_DUPLICADA = 'OPERACION_DUPLICADA';

    public function status(): int
    {
        return match ($this) {
            self::NO_AUTENTICADO => 401,
            self::NO_AUTORIZADO => 403,
            self::RECURSO_NO_ENCONTRADO => 404,
            self::DOCUMENTO_DUPLICADO,
            self::PRODUCTO_CODIGO_DUPLICADO,
            self::COMPRA_DOCUMENTO_DUPLICADO,
            self::CATEGORIA_NOMBRE_DUPLICADO,
            self::CORRELATIVO_EN_CONFLICTO,
            self::OPERACION_DUPLICADA => 409,
            self::CREDENCIALES_INVALIDAS,
            self::CAMPO_REQUERIDO,
            self::CAMPO_FORMATO_INVALIDO,
            self::CAMPO_FUERA_DE_RANGO,
            self::DOCUMENTO_INVALIDO,
            self::PRODUCTO_PRECIO_MAYOR_INVALIDO,
            self::PRODUCTO_INACTIVO,
            self::LOTE_VENCIMIENTO_PASADO,
            self::AJUSTE_SIN_MOTIVO,
            self::AJUSTE_CANTIDAD_NEGATIVA,
            self::COMPRA_SIN_LINEAS,
            self::STOCK_INSUFICIENTE,
            self::VENTA_SIN_LINEAS,
            self::TIPO_PRECIO_INVALIDO,
            self::LOTE_VENCIDO,
            self::FACTURA_REQUIERE_RUC,
            self::BOLETA_REQUIERE_DOCUMENTO,
            self::SERIE_NO_CONFIGURADA => 422,
        };
    }
}
