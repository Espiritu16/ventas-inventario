<?php

namespace App\Dominios\Inventario\Modelos;

use App\Dominios\Catalogo\Modelos\Producto;
use Database\Factories\LoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Porción de mercadería con un vencimiento y un costo propios.
 *
 * `fecha_vencimiento` es una **fecha civil**: la que está impresa en el
 * envase. No es un instante y no se convierte de zona — un lote que vence el
 * 30 vence el 30 en Lima y en cualquier lado. Por eso se guarda como `date` y
 * se escribe siempre en formato `Y-m-d`, sin pasar por un objeto con hora: es
 * la clave del orden FEFO, y correrla un día cambia qué se vende primero.
 */
class Lote extends Model
{
    use HasFactory;

    protected $table = 'lotes';

    protected $fillable = [
        'producto_id', 'codigo_lote', 'fecha_vencimiento',
        'cantidad_actual', 'costo_unitario',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'immutable_date:Y-m-d',
            'cantidad_actual' => 'string',
            'costo_unitario' => 'string',
        ];
    }

    /** @return BelongsTo<Producto, $this> */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** @return HasMany<MovimientoInventario, $this> */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /** Vencido se compara por fecha civil, nunca por instante. */
    public function estaVencido(?string $hoy = null): bool
    {
        return $this->fecha_vencimiento->format('Y-m-d') < ($hoy ?? now()->format('Y-m-d'));
    }

    public function tieneExistencia(): bool
    {
        return bccomp($this->cantidad_actual, '0', 3) > 0;
    }

    protected static function newFactory(): LoteFactory
    {
        return LoteFactory::new();
    }
}
