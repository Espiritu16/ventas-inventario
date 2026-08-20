<?php

namespace App\Dominios\Usuarios\Livewire;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Gestión de usuarios (RF-002): listado, alta y edición en la misma pantalla.
 *
 * Invoca `UsuarioService` en el mismo proceso, sin cliente HTTP de por medio
 * (ADR-0005 y la enmienda de 2026-08-19 en docs/contratos/usuarios.md). Las
 * firmas son del backend y este frente no las cambia.
 *
 * La validación no se repite acá. El servicio ya la hace y es el único lugar
 * donde debe estar: si la pantalla decidiera por su cuenta qué es válido,
 * podría aceptar algo que el servicio rechaza —o al revés— y las dos reglas
 * divergirían en silencio. Lo que hace la pantalla es mostrar el rechazo
 * donde corresponde.
 */
class ListaDeUsuarios extends Component
{
    /**
     * La búsqueda y la página viven en la URL, para que recargar, compartir
     * el enlace y usar atrás/adelante devuelvan lo mismo
     * (docs/frontend/integracion.md).
     */
    #[Url]
    public string $buscar = '';

    #[Url]
    public int $pagina = 1;

    /** null = alta; un id = edición de ese usuario. */
    public ?int $editando = null;

    public bool $formularioAbierto = false;

    public string $nombre = '';

    public string $email = '';

    public string $password = '';

    public string $rol = Usuario::ROL_VENDEDOR;

    /** Error de negocio: se muestra como aviso de la operación. */
    public ?string $error = null;

    /** Error de validación: se muestra junto al campo que lo produjo. */
    public ?string $errorDeCampo = null;

    public ?string $campoConError = null;

    public ?string $exito = null;

    public function mount(): void
    {
        $this->exigirPermiso('GET /usuarios');
    }

    public function updatedBuscar(): void
    {
        // Al cambiar la búsqueda se vuelve a la primera página: conservar la
        // página anterior mostraría un vacío que no es el de la búsqueda.
        $this->pagina = 1;
    }

    public function nuevo(): void
    {
        $this->limpiarFormulario();
        $this->formularioAbierto = true;
    }

    public function editar(int $id): void
    {
        $this->exigirPermiso('GET /usuarios');

        $usuario = Usuario::query()->find($id);

        if ($usuario === null) {
            $this->error = 'El usuario indicado ya no existe.';

            return;
        }

        $this->limpiarMensajes();

        $this->editando = $id;
        $this->nombre = $usuario->nombre;
        $this->email = $usuario->email;
        $this->rol = $usuario->rol;
        $this->password = '';
        $this->formularioAbierto = true;
    }

    public function cancelar(): void
    {
        $this->limpiarFormulario();
    }

    public function guardar(UsuarioService $usuarios): void
    {
        $this->exigirPermiso($this->editando === null ? 'POST /usuarios' : 'PATCH /usuarios/{id}');

        $this->limpiarMensajes();

        try {
            if ($this->editando === null) {
                $usuarios->crear(DatosUsuario::desde([
                    'nombre' => $this->nombre,
                    'email' => $this->email,
                    'password' => $this->password,
                    'rol' => $this->rol,
                ]));
            } else {
                // La contraseña no se cambia por esta vía, así que no se envía.
                $usuarios->actualizar($this->editando, DatosUsuario::desde([
                    'nombre' => $this->nombre,
                    'email' => $this->email,
                    'rol' => $this->rol,
                ]));
            }
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $this->editando === null
            ? 'Usuario creado.'
            : 'Usuario actualizado.';

        $this->limpiarFormulario();
    }

    /**
     * Activa o desactiva a alguien.
     *
     * El botón se ofrece también sobre la propia fila: quien lo intente recibe
     * el rechazo del servidor, que es la protección real. Ocultarlo dejaría a
     * quien opera sin saber por qué no puede, y haría que la única señal de la
     * regla fuera la ausencia de un botón —que no protege de nada, porque el
     * método se puede invocar igual.
     */
    public function cambiarEstado(int $id, bool $activo, UsuarioService $usuarios): void
    {
        $this->exigirPermiso('PATCH /usuarios/{id}');

        $this->limpiarMensajes();

        try {
            $usuarios->actualizar($id, DatosUsuario::desde(['activo' => $activo]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $activo ? 'Usuario activado.' : 'Usuario desactivado.';
    }

    /**
     * Comprueba el permiso antes de invocar el servicio.
     *
     * No es redundante con el middleware. El middleware protege la ruta
     * `GET /usuarios`, pero los métodos de este componente no viajan por esa
     * ruta: viajan por el endpoint de actualización de Livewire, que solo
     * exige sesión activa y no distingue rol. Sin esta comprobación, quien
     * tuviera cualquier sesión podía invocar `guardar` y darse de alta como
     * administrador, sin pasar nunca por la pantalla.
     *
     * `UsuarioService` tampoco cubre esto, y no debería: es un servicio de
     * dominio y no sabe quién lo llama. Por eso la matriz declara que el
     * componente comprueba el permiso y el servicio no confía en el
     * componente — son dos capas, no la misma dos veces.
     *
     * El permiso se deriva de la misma matriz que autoriza las rutas, con el
     * identificador de la operación que la tabla ya declara.
     */
    private function exigirPermiso(string $operacion): void
    {
        $usuario = Auth::user();

        if ($usuario === null || ! $usuario->activo || ! MatrizDePermisos::permiteA($operacion, $usuario)) {
            throw new ErrorDeDominio(
                CodigoDeError::NO_AUTORIZADO,
                'Tu rol no tiene permiso para esta operación.'
            );
        }
    }

    /**
     * Un rechazo con campo señalado es de validación y va junto al campo; uno
     * sin campo es de negocio y va como aviso de la operación
     * (docs/frontend/experiencia.md). El código nunca se muestra.
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
        $this->email = '';
        $this->password = '';
        $this->rol = Usuario::ROL_VENDEDOR;
        $this->errorDeCampo = null;
        $this->campoConError = null;
    }

    public function render()
    {
        return view('livewire.usuarios.lista-de-usuarios', [
            'usuarios' => app(UsuarioService::class)->listar(
                $this->buscar === '' ? null : $this->buscar,
                $this->pagina,
            ),
        ]);
    }
}
