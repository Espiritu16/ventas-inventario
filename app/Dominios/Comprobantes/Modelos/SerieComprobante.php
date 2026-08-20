<?php

namespace App\Dominios\Comprobantes\Modelos;

use Illuminate\Database\Eloquent\Model;

/**
 * Serie de numeración tributaria. Su `correlativo_actual` es el punto de
 * serialización de RF-014: se reserva con bloqueo de fila, nunca con
 * `MAX(correlativo)+1`, que no serializa nada y produce números repetidos bajo
 * concurrencia.
 */
class SerieComprobante extends Model
{
    public const TIPO_FACTURA = '01';

    public const TIPO_BOLETA = '03';

    protected $table = 'series_comprobante';

    protected $fillable = ['tipo_comprobante', 'serie', 'correlativo_actual', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'correlativo_actual' => 'integer'];
    }
}
