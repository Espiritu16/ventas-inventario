<?php

namespace Database\Factories;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Dominios\Clientes\Modelos\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cliente> */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'tipo_documento' => TipoDeDocumento::DNI->value,
            'numero_documento' => (string) $this->faker->unique()->numerify('########'),
            'nombre' => $this->faker->name(),
            'direccion' => $this->faker->address(),
            'telefono' => '987654321',
            'email' => $this->faker->unique()->safeEmail(),
            'activo' => true,
        ];
    }

    public function sinDocumento(): static
    {
        return $this->state(fn () => [
            'tipo_documento' => TipoDeDocumento::SIN_DOCUMENTO->value,
            'numero_documento' => null,
        ]);
    }

    public function conRuc(): static
    {
        return $this->state(fn () => [
            'tipo_documento' => TipoDeDocumento::RUC->value,
            'numero_documento' => (string) $this->faker->unique()->numerify('20#########'),
        ]);
    }
}
