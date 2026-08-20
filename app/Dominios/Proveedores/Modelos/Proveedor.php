<?php

namespace App\Dominios\Proveedores\Modelos;

use App\Compartido\Documentos\TipoDeDocumento;
use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Quien vende mercadería al negocio. Siempre tiene RUC: es quien emite la
 * factura de compra que sustenta el ingreso de stock.
 */
class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores';

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'razon_social',
        'direccion', 'telefono', 'email', 'activo',
    ];

    protected $attributes = ['tipo_documento' => TipoDeDocumento::RUC->value];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    protected static function newFactory(): ProveedorFactory
    {
        return ProveedorFactory::new();
    }
}
