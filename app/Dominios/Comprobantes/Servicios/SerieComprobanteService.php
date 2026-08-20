<?php

namespace App\Dominios\Comprobantes\Servicios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Comprobantes\Modelos\SerieComprobante;
use Illuminate\Support\Facades\DB;

/**
 * Reserva de correlativos tributarios (RF-014).
 *
 * El correlativo se toma bloqueando la fila de la serie dentro de la
 * transacción de la venta. No se usa `MAX(correlativo) + 1`: dos ventas
 * simultáneas leerían el mismo máximo y emitirían dos comprobantes con el
 * mismo número, que ante SUNAT son dos documentos con la misma identidad.
 * La unicidad de `(tipo, serie, correlativo)` en la base es la red por si algo
 * saltea este camino.
 */
class SerieComprobanteService
{
    /**
     * Reserva el siguiente correlativo de una serie activa.
     *
     * Debe invocarse **dentro** de la transacción de la venta: el bloqueo solo
     * vale mientras esa transacción esté abierta, y si el correlativo se
     * reservara aparte, una venta que falle después dejaría un número
     * consumido y un hueco en la numeración.
     */
    public function reservarCorrelativo(string $tipoComprobante): array
    {
        if (! DB::transactionLevel()) {
            throw new \LogicException(
                'reservarCorrelativo debe ejecutarse dentro de la transacción de la venta: '
                .'fuera de ella el bloqueo no protege nada y un fallo posterior dejaría un hueco en la numeración.'
            );
        }

        $serie = SerieComprobante::query()
            ->where('tipo_comprobante', $tipoComprobante)
            ->where('activo', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();

        if ($serie === null) {
            throw new ErrorDeDominio(
                CodigoDeError::SERIE_NO_CONFIGURADA,
                'No hay una serie activa configurada para ese tipo de comprobante.',
                ['campo' => 'tipo_comprobante']
            );
        }

        $serie->correlativo_actual = $serie->correlativo_actual + 1;
        $serie->save();

        return ['serie' => $serie, 'correlativo' => $serie->correlativo_actual];
    }
}
