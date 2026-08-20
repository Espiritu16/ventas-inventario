<?php

namespace Database\Factories;

use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lote> */
class LoteFactory extends Factory
{
    protected $model = Lote::class;

    public function definition(): array
    {
        return [
            'producto_id' => Producto::factory(),
            'codigo_lote' => strtoupper($this->faker->bothify('L##-###')),
            'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
            'cantidad_actual' => '10.000',
            'costo_unitario' => '5.0000',
        ];
    }

    public function vencido(): static
    {
        return $this->state(fn () => ['fecha_vencimiento' => now()->subDay()->format('Y-m-d')]);
    }

    public function venceEl(string $fecha): static
    {
        return $this->state(fn () => ['fecha_vencimiento' => $fecha]);
    }

    public function sinExistencia(): static
    {
        return $this->state(fn () => ['cantidad_actual' => '0.000']);
    }
}
