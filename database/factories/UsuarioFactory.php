<?php

namespace Database\Factories;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Usuario> */
class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'contrasena-de-prueba',
            'rol' => Usuario::ROL_VENDEDOR,
            'activo' => true,
        ];
    }

    public function administrador(): static
    {
        return $this->state(fn () => ['rol' => Usuario::ROL_ADMINISTRADOR]);
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }
}
