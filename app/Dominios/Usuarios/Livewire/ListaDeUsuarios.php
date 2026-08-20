<?php

namespace App\Dominios\Usuarios\Livewire;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Interfaz\MuestraRechazosDeDominio;
use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Usuarios\Servicios\UsuarioService;
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
 *
 * El atributo de la clase gobierna lo que `render()` sirve; los métodos que
 * escriben declaran el suyo. Los de interfaz —`nuevo`, `editar`, `cancelar`—
 * heredan el de la clase: no son operaciones distintas de la pantalla, son esa
 * pantalla.
 */
#[Permiso('GET /usuarios')]
class ListaDeUsuarios extends Component
{
    use MuestraRechazosDeDominio;

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

    /**
     * El alta y la edición son dos métodos y no uno con una condición, porque
     * el permiso se resuelve por reflexión antes de ejecutar el método: es
     * estático y no puede depender de `$editando`.
     *
     * Un solo `guardar()` declarando `POST /usuarios` dejaría las ediciones
     * autorizadas por un permiso de creación. Hoy funcionaría igual —las dos
     * filas son del administrador— y eso es justamente lo que lo vuelve
     * peligroso: el día que alguien pueda crear pero no editar, el sistema
     * autorizaría ediciones que la matriz prohíbe y ninguna prueba fallaría,
     * porque hoy las dos coinciden.
     */
    #[Permiso('POST /usuarios')]
    public function crear(UsuarioService $usuarios): void
    {
        $this->limpiarMensajes();

        try {
            $usuarios->crear(DatosUsuario::desde([
                'nombre' => $this->nombre,
                'email' => $this->email,
                'password' => $this->password,
                'rol' => $this->rol,
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Usuario creado.';

        $this->limpiarFormulario();
    }

    #[Permiso('PATCH /usuarios/{id}')]
    public function actualizar(UsuarioService $usuarios): void
    {
        $this->limpiarMensajes();

        try {
            // La contraseña no se cambia por esta vía, así que no se envía.
            $usuarios->actualizar((int) $this->editando, DatosUsuario::desde([
                'nombre' => $this->nombre,
                'email' => $this->email,
                'rol' => $this->rol,
            ]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = 'Usuario actualizado.';

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
    #[Permiso('PATCH /usuarios/{id}')]
    public function cambiarEstado(int $id, bool $activo, UsuarioService $usuarios): void
    {
        $this->limpiarMensajes();

        try {
            $usuarios->actualizar($id, DatosUsuario::desde(['activo' => $activo]));
        } catch (ErrorDeDominio $fallo) {
            $this->mostrar($fallo);

            return;
        }

        $this->exito = $activo ? 'Usuario activado.' : 'Usuario desactivado.';
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

    /**
     * La comprobación vive acá, donde se sirven los datos, y no en `mount()`.
     *
     * `mount()` corre una sola vez: en cada interacción posterior el componente
     * se hidrata desde el snapshot que el navegador guardó y `render()` vuelve
     * a consultar sin pasar por el montaje. Comprobar solo al montar protege la
     * primera carga y nada más — a quien se le cambie el rol con la pantalla
     * abierta seguiría viendo el listado hasta recargar.
     *
     * El middleware tampoco lo cubre: el endpoint de actualización de Livewire
     * exige sesión activa pero no comprueba rol, porque es el mismo endpoint
     * para todos los componentes.
     */
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
