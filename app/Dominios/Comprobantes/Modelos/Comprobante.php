<?php

namespace App\Dominios\Comprobantes\Modelos;

use App\Dominios\Ventas\Modelos\Venta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Documento tributario. Nace `PENDIENTE` y ahí se queda: el envío a SUNAT es
 * de S-06-B (ADR-0003).
 *
 * `fecha_emision` es una fecha civil en zona de Lima. Una venta cerrada a las
 * 23:40 no puede quedar emitida con la fecha del día siguiente: el correlativo
 * y la fecha tienen que ser coherentes ante SUNAT, y el resumen diario de
 * boletas se agrupa por este campo.
 */
class Comprobante extends Model
{
    public const ESTADO_PENDIENTE = 'PENDIENTE';

    public const ESTADO_ENVIADO = 'ENVIADO';

    public const ESTADO_ACEPTADO = 'ACEPTADO';

    public const ESTADO_RECHAZADO = 'RECHAZADO';

    protected $table = 'comprobantes';

    protected $fillable = [
        'venta_id', 'serie_comprobante_id', 'tipo_comprobante', 'serie',
        'correlativo', 'fecha_emision', 'estado', 'intentos',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'immutable_date:Y-m-d',
            'correlativo' => 'integer',
            'intentos' => 'integer',
            'enviado_en' => 'immutable_datetime',
            'respondido_en' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<SerieComprobante, $this> */
    public function serieComprobante(): BelongsTo
    {
        return $this->belongsTo(SerieComprobante::class);
    }

    /** Número tal como se imprime: F001-00000123. */
    public function numeroFormateado(): string
    {
        return $this->serie.'-'.str_pad((string) $this->correlativo, 8, '0', STR_PAD_LEFT);
    }
}
