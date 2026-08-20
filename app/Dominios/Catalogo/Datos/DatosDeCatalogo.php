<?php

namespace App\Dominios\Catalogo\Datos;

/**
 * Entrada de creación y de edición del catálogo.
 *
 * En la edición cada campo es opcional: ausente significa "no cambia", así
 * que se distingue con `fueEnviado()` y no por el valor, que puede ser null
 * legítimamente en `descripcion`.
 */
final class DatosDeCatalogo
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
