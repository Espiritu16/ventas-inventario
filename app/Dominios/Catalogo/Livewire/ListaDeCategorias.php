<?php

namespace App\Dominios\Catalogo\Livewire;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Servicios\CategoriaService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Categorías del catálogo (RF-003): listado, alta y edición en la misma
 * pantalla, sin cambiar de ruta.
 *
 * Invoca `CategoriaService` en el mismo proceso; no hay API HTTP entre la
 * interfaz y el dominio (ADR-0006). La validación vive en el servicio y no se
 * repite acá: la pantalla solo decide dónde mostrar el rechazo.
 */
#[Permiso('GET /categorias')]
class ListaDeCategorias extends Component
{
    /**
     * Ver las inactivas vive en la URL, como los demás filtros: recargar o
     * compartir el enlace tiene que mostrar lo mismo.
     */
    #[Url]
    public bool $incluirInactivas = false;

    public bool $formularioAbierto = false;

    /** null = alta; un id = edición de esa categoría. */
    public ?int $editando = null;

    public string $nombre = '';

    public string $descripcion = '';

    /** Error de negocio: aviso de la operación. */
    public ?string $error = null;

    /** Error de validación: va junto al campo que lo produjo. */
    public ?string $errorDeCampo = null;

    public ?string $campoConError = null;

    public ?string $exito = null;

    public function nuevo(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id, CategoriaService $categorias): void
    {
        $this->limpiarMensajes();

        try {
            $categoria = $categorias->encontrar($id);
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->editando = $id;
        $this->nombre = $categoria->nombre;
        $this->descripcion = (string) $categoria->descripcion;
        $this->formularioAbierto = true;
    }

    public function cancelar(): void
    {
        $this->limpiarFormulario();
    }

    /**
     * El alta y la edición son dos métodos y no uno con una condición: el
     * permiso se resuelve por reflexión antes de ejecutar, así que es estático
     * y no puede depender de `$editando`.
     */
    #[Permiso('POST /categorias')]
    public function crear(CategoriaService $categorias): void
    {
        $this->limpiarMensajes();

        try {
            $categorias->crear($this->datos());
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Categoría creada.';
        $this->limpiarFormulario();
    }

    #[Permiso('PATCH /categorias/{id}')]
    public function actualizar(CategoriaService $categorias): void
    {
        $this->limpiarMensajes();

        try {
            $categorias->actualizar((int) $this->editando, $this->datos());
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Categoría actualizada.';
        $this->limpiarFormulario();
    }

    /**
     * Activar o desactivar se refleja en la lista sin recargar la página:
     * `render()` vuelve a consultar y la fila cambia sola.
     */
    #[Permiso('PATCH /categorias/{id}')]
    public function cambiarEstado(int $id, bool $activo, CategoriaService $categorias): void
    {
        $this->limpiarMensajes();

        try {
            $categorias->actualizar($id, DatosDeCatalogo::desde(['activo' => $activo]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $activo ? 'Categoría activada.' : 'Categoría desactivada.';
    }

    private function datos(): DatosDeCatalogo
    {
        return DatosDeCatalogo::desde([
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion === '' ? null : $this->descripcion,
        ]);
    }

    /**
     * Un rechazo que señala un campo es de validación y va junto a él; uno sin
     * campo es de negocio y va como aviso de la operación
     * (docs/frontend/experiencia.md). El código nunca se le muestra a nadie.
     */
    private function mostrar(ErrorDeDominio $fallo): void
    {
        $campo = $fallo->detalle['campo'] ?? null;

        if (is_string($campo)) {
            $this->campoConError = $campo;
            $this->errorDeCampo = $fallo->getMessage();

            return;
        }

        $this->error = $fallo->getMessage();
    }

    private function limpiarMensajes(): void
    {
        $this->error = null;
        $this->errorDeCampo = null;
        $this->campoConError = null;
        $this->exito = null;
    }

    private function limpiarFormulario(): void
    {
        $this->editando = null;
        $this->formularioAbierto = false;
        $this->nombre = '';
        $this->descripcion = '';
        $this->errorDeCampo = null;
        $this->campoConError = null;
    }

    public function render()
    {
        return view('livewire.catalogo.lista-de-categorias', [
            'categorias' => app(CategoriaService::class)->listar($this->incluirInactivas),
        ]);
    }
}
