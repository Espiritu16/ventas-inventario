<?php

namespace App\Dominios\Ventas\Modelos;

use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Modelos\Comprobante;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    public const METODOS_PAGO = ['efectivo', 'tarjeta', 'billetera'];

    protected $table = 'ventas';

    protected $fillable = ['cliente_id', 'usuario_id', 'fecha', 'subtotal', 'igv', 'total', 'metodo_pago'];

    protected function casts(): array
    {
        return [
            'fecha' => 'immutable_datetime',
            'subtotal' => 'string',
            'igv' => 'string',
            'total' => 'string',
        ];
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return HasMany<DetalleVenta, $this> */
    public function lineas(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    /** @return HasOne<Comprobante, $this> */
    public function comprobante(): HasOne
    {
        return $this->hasOne(Comprobante::class);
    }
}
