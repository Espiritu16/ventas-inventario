<?php

namespace App\Compartido\Datos;

/**
 * Entrada de creación y de edición.
 *
 * Un campo ausente significa "no cambia", y eso no se puede distinguir por el
 * valor: `null` es un valor legítimo en dirección, teléfono o correo. Por eso
 * la diferencia se consulta con `fueEnviado()`.
 */
final class DatosDeEntrada
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
