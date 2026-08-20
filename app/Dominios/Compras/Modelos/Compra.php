<?php

namespace App\Dominios\Compras\Modelos;

use App\Dominios\Proveedores\Modelos\Proveedor;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Documento de ingreso de mercadería. Es lo único que crea existencias. */
class Compra extends Model
{
    public const TIPOS_DOCUMENTO = ['01', '03', '09', 'NA'];

    protected $table = 'compras';

    protected $fillable = [
        'proveedor_id', 'tipo_documento', 'serie_documento',
        'numero_documento', 'fecha_emision', 'total', 'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'immutable_date:Y-m-d',
            'total' => 'string',
        ];
    }

    /** @return BelongsTo<Proveedor, $this> */
    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /** @return BelongsTo<Usuario, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /** @return HasMany<DetalleCompra, $this> */
    public function lineas(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }
}
