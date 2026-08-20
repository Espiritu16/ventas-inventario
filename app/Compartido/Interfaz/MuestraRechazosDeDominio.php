<?php

namespace App\Compartido\Interfaz;

use App\Compartido\Errores\ErrorDeDominio;

/**
 * Cómo una pantalla muestra un rechazo del dominio.
 *
 * No es un detalle de implementación de ninguna pantalla: es el consumo de una
 * regla transversal ya aprobada. `docs/errores/manejo-errores.md` fija que un
 * error de campo nombra su campo en `detalle`, y `docs/frontend/experiencia.md`
 * que los de validación van junto al campo y los de negocio como aviso de la
 * operación. Una regla con una sola fuente no debería tener cinco
 * consumidores que la reimplementan.
 *
 * Vive acá y no en un dominio porque lo comparten componentes de dominios
 * distintos, y no del lado de backend porque manipula estado de un componente
 * Livewire —qué campo quedó marcado, qué mensaje se muestra dónde—, que son
 * conceptos de la interfaz. Área declarada en la enmienda del 2026-08-20 de
 * `AGENTS.md`.
 *
 * El código de la taxonomía nunca se le muestra a quien opera: se registra,
 * no se enseña.
 */
trait MuestraRechazosDeDominio
{
    /** Error de negocio: aviso de la operación. */
    public ?string $error = null;

    /** Error de validación: el texto que va junto a un campo. */
    public ?string $errorDeCampo = null;

    /** Qué campo señaló el rechazo, o null si no señaló ninguno. */
    public ?string $campoConError = null;

    public ?string $exito = null;

    /**
     * Un rechazo que señala un campo es de validación y va junto a él; uno sin
     * campo es de negocio y va como aviso de la operación.
     *
     * La correspondencia código→campo **no se deduce acá**: depende del
     * servicio que lanzó el rechazo —`DOCUMENTO_DUPLICADO` es
     * `numero_documento` en clientes y proveedores, pero el mismo código
     * significa otra cosa en otro dominio—. Por eso se lee de `detalle` y no
     * se infiere del código: inferirlo crearía un segundo lugar donde vive esa
     * equivalencia.
     */
    protected function mostrar(ErrorDeDominio $fallo): void
    {
        $campo = $fallo->detalle['campo'] ?? null;

        if (is_string($campo)) {
            $this->campoConError = $campo;
            $this->errorDeCampo = $fallo->getMessage();

            return;
        }

        $this->error = $fallo->getMessage();
    }

    /**
     * El texto que le corresponde a un campo, o null si el rechazo fue de otro.
     *
     * Lo usa la vista para no repetir la comparación en cada campo. Es una
     * lectura del estado del componente y no cambia nada; el mecanismo de
     * permisos la alcanza igual, porque cubre toda invocación del componente.
     */
    public function errorDe(string $campo): ?string
    {
        return $this->campoConError === $campo ? $this->errorDeCampo : null;
    }

    protected function limpiarMensajes(): void
    {
        $this->error = null;
        $this->errorDeCampo = null;
        $this->campoConError = null;
        $this->exito = null;
    }
}
