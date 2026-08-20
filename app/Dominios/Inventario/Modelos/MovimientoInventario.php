<?php

namespace App\Dominios\Inventario\Modelos;

use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una línea del kardex. Append-only: la base le revoca UPDATE y DELETE a la
 * aplicación, así que una corrección se registra como un ajuste nuevo y nunca
 * modificando el historial (RNF-004, ADR-0004).
 *
 * No lleva `updated_at`: no hay actualización posible que registrar.
 */
class MovimientoInventario extends Model
{
    public const TIPO_INGRESO = 'ingreso';

    public const TIPO_SALIDA = 'salida';

    public const TIPO_AJUSTE = 'ajuste';

    public const ORIGEN_COMPRA = 'compra';

    public const ORIGEN_VENTA = 'venta';

    public const ORIGEN_AJUSTE = 'ajuste';

    protected $table = 'movimientos_inventario';

    public const UPDATED_AT = null;

    protected $fillable = [
        'lote_id', 'producto_id', 'tipo', 'cantidad', 'costo_unitario',
        'motivo', 'origen_tipo', 'origen_id', 'usuario_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'string',
            'costo_unitario' => 'string',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Lote, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
