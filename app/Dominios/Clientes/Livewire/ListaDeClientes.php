<?php

namespace App\Dominios\Clientes\Livewire;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Interfaz\MuestraRechazosDeDominio;
use App\Dominios\Clientes\Servicios\ClienteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Clientes (RF-010): listado, alta y edición.
 *
 * Se monta también como diálogo desde la pantalla de venta (RF-011): en ese
 * modo muestra solo el formulario y, al crear, emite `cliente-creado` con el
 * identificador para que quien lo abrió lo deje seleccionado. Por eso el alta
 * la puede hacer el vendedor y la edición no — así lo declara la matriz.
 */
#[Permiso('GET /clientes')]
class ListaDeClientes extends Component
{
    use MuestraRechazosDeDominio;

    #[Url]
    public string $buscar = '';

    #[Url]
    public int $pagina = 1;

    /** Montado desde otra pantalla: solo el formulario, sin listado. */
    public bool $comoDialogo = false;

    public bool $formularioAbierto = false;

    public ?int $editando = null;

    public string $tipoDocumento = TipoDeDocumento::DNI->value;

    public string $numeroDocumento = '';

    public string $nombre = '';

    public string $direccion = '';

    public string $telefono = '';

    public string $email = '';

    /**
     * Aviso inmediato del documento, que cambia según el tipo elegido.
     *
     * El patrón se le pide a `TipoDeDocumento`, la misma fuente que usa el
     * servidor: un DNI son ocho dígitos y un RUC once, y esas reglas no se
     * copian acá. Es comodidad — el servicio vuelve a validar igual.
     */
    public ?string $avisoDeDocumento = null;

    public function mount(bool $comoDialogo = false): void
    {
        $this->comoDialogo = $comoDialogo;
        $this->formularioAbierto = $comoDialogo;
    }

    public function updatedBuscar(): void
    {
        $this->pagina = 1;
    }

    public function updatedTipoDocumento(): void
    {
        // El número que servía para un tipo puede no servir para el otro.
        $this->revisarDocumento();
    }

    public function updatedNumeroDocumento(): void
    {
        $this->revisarDocumento();
    }

    private function revisarDocumento(): void
    {
        $this->avisoDeDocumento = null;

        $tipo = TipoDeDocumento::tryFrom($this->tipoDocumento);

        if ($tipo === null) {
            return;
        }

        if (! $tipo->exigeNumero()) {
            if ($this->numeroDocumento !== '') {
                $this->avisoDeDocumento = 'Un cliente sin documento no lleva número.';
            }

            return;
        }

        if ($this->numeroDocumento === '') {
            return;
        }

        if (preg_match((string) $tipo->patron(), $this->numeroDocumento) !== 1) {
            $this->avisoDeDocumento = "El número de {$tipo->descripcion()} no tiene el formato esperado.";
        }
    }

    /** Comodidad del listado: quien no puede editar no ve el botón. La protección es del hook. */
    public function puedeEditar(): bool
    {
        $usuario = Auth::user();

        return $usuario !== null && MatrizDePermisos::permiteA('PATCH /clientes/{id}', $usuario);
    }

    public function nuevo(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id, ClienteService $clientes): void
    {
        $this->limpiarMensajes();

        try {
            $cliente = $clientes->encontrar($id);
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->editando = $id;
        $this->tipoDocumento = $cliente->tipo_documento;
        $this->numeroDocumento = (string) $cliente->numero_documento;
        $this->nombre = $cliente->nombre;
        $this->direccion = (string) $cliente->direccion;
        $this->telefono = (string) $cliente->telefono;
        $this->email = (string) $cliente->email;
        $this->formularioAbierto = true;
        $this->avisoDeDocumento = null;
    }

    public function cancelar(): void
    {
        $this->limpiarFormulario();

        if ($this->comoDialogo) {
            $this->dispatch('alta-de-cliente-cancelada');
        }
    }

    #[Permiso('POST /clientes')]
    public function crear(ClienteService $clientes): void
    {
        $this->limpiarMensajes();

        try {
            $cliente = $clientes->crear(DatosDeEntrada::desde([
                'tipo_documento' => $this->tipoDocumento,
                'numero_documento' => $this->numeroDocumento,
                'nombre' => $this->nombre,
                'direccion' => $this->opcional($this->direccion),
                'telefono' => $this->opcional($this->telefono),
                'email' => $this->opcional($this->email),
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Cliente creado.';

        // Quien abrió el diálogo lo necesita seleccionado: se le devuelve el
        // identificador en vez de obligarlo a buscarlo de nuevo.
        if ($this->comoDialogo) {
            $this->dispatch('cliente-creado', clienteId: $cliente->id);
        }

        $this->limpiarFormulario();
    }

    /** El documento no se envía: no es modificable. */
    #[Permiso('PATCH /clientes/{id}')]
    public function actualizar(ClienteService $clientes): void
    {
        $this->limpiarMensajes();

        try {
            $clientes->actualizar((int) $this->editando, DatosDeEntrada::desde([
                'nombre' => $this->nombre,
                'direccion' => $this->opcional($this->direccion),
                'telefono' => $this->opcional($this->telefono),
                'email' => $this->opcional($this->email),
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Cliente actualizado.';
        $this->limpiarFormulario();
    }

    private function opcional(string $valor): ?string
    {
        return $valor === '' ? null : $valor;
    }

    private function limpiarFormulario(): void
    {
        $this->editando = null;
        $this->formularioAbierto = $this->comoDialogo;
        $this->tipoDocumento = TipoDeDocumento::DNI->value;
        $this->numeroDocumento = '';
        $this->nombre = '';
        $this->direccion = '';
        $this->telefono = '';
        $this->email = '';
        $this->avisoDeDocumento = null;
        $this->errorDeCampo = null;
        $this->campoConError = null;
    }

    public function render()
    {
        return view('livewire.clientes.lista-de-clientes', [
            'clientes' => $this->comoDialogo
                ? null
                : app(ClienteService::class)->listar(
                    $this->buscar === '' ? null : $this->buscar,
                    $this->pagina,
                ),
            'tipos' => TipoDeDocumento::cases(),
        ]);
    }
}
