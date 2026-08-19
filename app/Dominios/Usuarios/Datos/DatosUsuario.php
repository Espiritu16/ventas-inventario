<?php

namespace App\Dominios\Usuarios\Datos;

/**
 * Entrada de creación y de edición de un usuario.
 *
 * En la edición cada campo es opcional: ausente significa "no cambia", y el
 * contrato rechaza `null` explícito, así que se distingue uno de otro con
 * `fueEnviado()` en vez de con el valor.
 */
final class DatosUsuario
{
    /** @param  array<string, mixed>  $campos */
    private function __construct(private readonly array $campos) {}

    /** @param  array<string, mixed>  $campos */
    public static function desde(array $campos): self
    {
        return new self($campos);
    }

    public function fueEnviado(string $campo): bool
    {
        return array_key_exists($campo, $this->campos);
    }

    public function valor(string $campo): mixed
    {
        return $this->campos[$campo] ?? null;
    }

    /** @return array<string, mixed> */
    public function todos(): array
    {
        return $this->campos;
    }
}
