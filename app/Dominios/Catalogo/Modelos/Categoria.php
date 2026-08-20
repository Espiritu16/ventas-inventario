<?php

namespace App\Dominios\Catalogo\Modelos;

use Database\Factories\CategoriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Agrupación de productos. Desactivarla no toca a los productos ya asociados:
 * dejan de poder crearse nuevos en ella, pero los existentes conservan su
 * categoría y siguen operativos (RF-003).
 */
class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** @return HasMany<Producto, $this> */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    protected static function newFactory(): CategoriaFactory
    {
        return CategoriaFactory::new();
    }
}
