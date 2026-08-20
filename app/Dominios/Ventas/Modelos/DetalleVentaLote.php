<?php

namespace App\Dominios\Ventas\Modelos;

use App\Dominios\Inventario\Modelos\Lote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Qué porción de una línea salió de cada lote, con el costo congelado al
 * momento de la salida: el reporte de utilidad no puede depender de que el
 * lote siga existiendo con el mismo costo.
 */
class DetalleVentaLote extends Model
{
    protected $table = 'detalle_venta_lotes';

    public $timestamps = false;

    protected $fillable = ['detalle_venta_id', 'lote_id', 'cantidad', 'costo_unitario'];

    protected function casts(): array
    {
        return ['cantidad' => 'string', 'costo_unitario' => 'string'];
    }

    /** @return BelongsTo<DetalleVenta, $this> */
    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(DetalleVenta::class);
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
