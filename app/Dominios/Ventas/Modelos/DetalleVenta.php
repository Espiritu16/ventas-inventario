<?php

namespace App\Dominios\Ventas\Modelos;

use App\Dominios\Catalogo\Modelos\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleVenta extends Model
{
    public const TIPOS_PRECIO = ['menor', 'mayor'];

    protected $table = 'detalle_ventas';

    protected $fillable = ['venta_id', 'producto_id', 'cantidad', 'tipo_precio', 'precio_unitario', 'importe'];

    protected function casts(): array
    {
        return ['cantidad' => 'string', 'precio_unitario' => 'string', 'importe' => 'string'];
    }

    /** @return BelongsTo<Venta, $this> */
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return HasMany<DetalleVentaLote, $this> */
    public function reparto(): HasMany
    {
        return $this->hasMany(DetalleVentaLote::class);
    }
}
