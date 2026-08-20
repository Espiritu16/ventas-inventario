<?php

namespace App\Dominios\Clientes\Modelos;

use App\Compartido\Documentos\TipoDeDocumento;
use Database\Factories\ClienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A quien se le vende. Puede no tener documento: una boleta por debajo del
 * tope no exige identificar a nadie, y ese cliente sin documento es el
 * "público general" con el que opera la caja.
 */
class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento', 'numero_documento', 'nombre',
        'direccion', 'telefono', 'email', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tipoDeDocumento(): TipoDeDocumento
    {
        return TipoDeDocumento::from($this->tipo_documento);
    }

    public function tieneDocumento(): bool
    {
        return $this->numero_documento !== null;
    }

    protected static function newFactory(): ClienteFactory
    {
        return ClienteFactory::new();
    }
}
