<?php

namespace App\Dominios\Proveedores\Livewire;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Interfaz\MuestraRechazosDeDominio;
use App\Dominios\Proveedores\Servicios\ProveedorService;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Proveedores (RF-005): listado paginado, alta y edición en la misma pantalla.
 *
 * Un proveedor siempre es RUC, así que no hay tipo que elegir. El documento no
 * se puede cambiar una vez creado: identifica al proveedor en las compras ya
 * registradas.
 */
#[Permiso('GET /proveedores')]
class ListaDeProveedores extends Component
{
    use MuestraRechazosDeDominio;

    #[Url]
    public string $buscar = '';

    #[Url]
    public int $pagina = 1;

    #[Url]
    public bool $incluirInactivos = false;

    public bool $formularioAbierto = false;

    public ?int $editando = null;

    public string $numeroDocumento = '';

    public string $razonSocial = '';

    public string $direccion = '';

    public string $telefono = '';

    public string $email = '';

    /**
     * Aviso inmediato del RUC, mientras se escribe.
     *
     * El patrón se le pide a `TipoDeDocumento`, que es el mismo que usa el
     * servidor para rechazar. Copiarlo acá crearía dos reglas que hay que
     * mantener iguales, y un RUC aceptado por la pantalla y rechazado por el
     * servidor —o al revés— es justo lo que eso produce.
     *
     * Es comodidad: el servicio vuelve a validar aunque esto se evite.
     */
    public ?string $avisoDeDocumento = null;

    public function updatedBuscar(): void
    {
        $this->pagina = 1;
    }

    public function updatedNumeroDocumento(): void
    {
        $this->avisoDeDocumento = null;

        if ($this->numeroDocumento === '') {
            return;
        }

        $patron = (string) TipoDeDocumento::RUC->patron();

        if (preg_match($patron, $this->numeroDocumento) !== 1) {
            $this->avisoDeDocumento = 'El número de RUC no tiene el formato esperado: son 11 dígitos.';
        }
    }

    public function nuevo(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id, ProveedorService $proveedores): void
    {
        $this->limpiarMensajes();

        try {
            $proveedor = $proveedores->encontrar($id);
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->editando = $id;
        $this->numeroDocumento = $proveedor->numero_documento;
        $this->razonSocial = $proveedor->razon_social;
        $this->direccion = (string) $proveedor->direccion;
        $this->telefono = (string) $proveedor->telefono;
        $this->email = (string) $proveedor->email;
        $this->formularioAbierto = true;
        $this->avisoDeDocumento = null;
    }

    public function cancelar(): void
    {
        $this->limpiarFormulario();
    }

    #[Permiso('POST /proveedores')]
    public function crear(ProveedorService $proveedores): void
    {
        $this->limpiarMensajes();

        try {
            $proveedores->crear(DatosDeEntrada::desde([
                'numero_documento' => $this->numeroDocumento,
                'razon_social' => $this->razonSocial,
                'direccion' => $this->opcional($this->direccion),
                'telefono' => $this->opcional($this->telefono),
                'email' => $this->opcional($this->email),
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Proveedor creado.';
        $this->limpiarFormulario();
    }

    /** El documento no se envía: no es modificable y mandarlo sería pedir un rechazo. */
    #[Permiso('PATCH /proveedores/{id}')]
    public function actualizar(ProveedorService $proveedores): void
    {
        $this->limpiarMensajes();

        try {
            $proveedores->actualizar((int) $this->editando, DatosDeEntrada::desde([
                'razon_social' => $this->razonSocial,
                'direccion' => $this->opcional($this->direccion),
                'telefono' => $this->opcional($this->telefono),
                'email' => $this->opcional($this->email),
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Proveedor actualizado.';
        $this->limpiarFormulario();
    }

    #[Permiso('PATCH /proveedores/{id}')]
    public function cambiarEstado(int $id, bool $activo, ProveedorService $proveedores): void
    {
        $this->limpiarMensajes();

        try {
            $proveedores->actualizar($id, DatosDeEntrada::desde(['activo' => $activo]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $activo ? 'Proveedor activado.' : 'Proveedor desactivado.';
    }

    private function opcional(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }

    private function limpiarFormulario(): void
    {
        $this->editando = null;
        $this->formularioAbierto = false;
        $this->numeroDocumento = '';
        $this->razonSocial = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->email = '';
        $this->avisoDeDocumento = null;
        $this->errorDeCampo = null;
        $this->campoConError = null;
    }

    public function render()
    {
        return view('livewire.proveedores.lista-de-proveedores', [
            'proveedores' => app(ProveedorService::class)->listar(
                $this->buscar === '' ? null : $this->buscar,
                ! $this->incluirInactivos,
                $this->pagina,
            ),
        ]);
    }
}
