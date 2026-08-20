<?php

namespace App\Dominios\Catalogo\Modelos;

use App\Dominios\Inventario\Modelos\Lote;
use Database\Factories\ProductoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Artículo del catálogo, con su precio al por menor y al por mayor.
 *
 * Los precios se leen como cadena y no como float: son `numeric(12,4)` en la
 * base y convertirlos a punto flotante perdería exactitud antes del redondeo
 * del comprobante (RNF-006).
 *
 * No tiene existencias: el stock vive en los lotes que crea la compra.
 */
class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'codigo', 'nombre', 'categoria_id', 'unidad_medida',
        'precio_menor', 'precio_mayor', 'stock_minimo', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_menor' => 'string',
            'precio_mayor' => 'string',
            'stock_minimo' => 'string',
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<Lote, $this> */
    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    protected static function newFactory(): ProductoFactory
    {
        return ProductoFactory::new();
    }
}
