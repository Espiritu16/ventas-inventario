<?php

namespace App\Compartido\Errores;

use RuntimeException;

/**
 * Rechazo previsto por el contrato: el sistema dice que no y por qué, con un
 * código de la taxonomía. No es una falla del sistema, así que no se registra
 * como error interno.
 */
class ErrorDeDominio extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $detalle  información accionable; nunca
     *                                         credenciales, rutas internas ni trazas (RNF-014)
     */
    public function __construct(
        public readonly CodigoDeError $codigo,
        string $mensaje,
        public readonly array $detalle = [],
    ) {
        parent::__construct($mensaje);
    }

    public function status(): int
    {
        return $this->codigo->status();
    }

    /** @return array<string, mixed> */
    public function comoRespuesta(): array
    {
        return ['error' => [
            'codigo' => $this->codigo->value,
            'mensaje' => $this->getMessage(),
            'detalle' => (object) $this->detalle,
        ]];
    }
}
