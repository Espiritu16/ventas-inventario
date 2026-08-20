<?php

namespace App\Dominios\Compras\Modelos;

use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Línea de compra. Guarda el lote que creó o incrementó. */
class DetalleCompra extends Model
{
    protected $table = 'detalle_compras';

    protected $fillable = [
        'compra_id', 'producto_id', 'cantidad', 'costo_unitario',
        'codigo_lote', 'fecha_vencimiento', 'lote_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'immutable_date:Y-m-d',
            'cantidad' => 'string',
            'costo_unitario' => 'string',
        ];
    }

    /** @return BelongsTo<Compra, $this> */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}
