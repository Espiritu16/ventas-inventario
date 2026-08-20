<?php

namespace Database\Factories;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Dominios\Proveedores\Modelos\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Proveedor> */
class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'tipo_documento' => TipoDeDocumento::RUC->value,
            'numero_documento' => (string) $this->faker->unique()->numerify('20#########'),
            'razon_social' => $this->faker->company(),
            'direccion' => $this->faker->address(),
            'telefono' => '987654321',
            'email' => $this->faker->unique()->safeEmail(),
            'activo' => true,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
