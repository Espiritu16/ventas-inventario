<?php

namespace Database\Factories;

use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Modelos\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Producto> */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        $menor = $this->faker->randomFloat(4, 2, 500);

        return [
            'codigo' => strtoupper($this->faker->unique()->bothify('??##-###')),
            'nombre' => ucfirst($this->faker->words(3, true)),
            'categoria_id' => Categoria::factory(),
            'unidad_medida' => 'NIU',
            'precio_menor' => number_format($menor, 4, '.', ''),
            'precio_mayor' => number_format($menor * 0.8, 4, '.', ''),
            'stock_minimo' => '0.000',
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
