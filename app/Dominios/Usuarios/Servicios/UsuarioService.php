<?php

namespace App\Dominios\Usuarios\Servicios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Servicios de usuario del contrato docs/contratos/servicios-de-dominio.md.
 *
 * La validación vive aquí y no en la capa HTTP porque los componentes
 * Livewire invocan estos métodos directamente, sin pasar por una ruta: si
 * validara el controlador, la interfaz quedaría sin validar.
 */
class UsuarioService
{
    private const POR_PAGINA = 20;

    /**
     * Hash contra el que se compara cuando el correo no existe.
     *
     * Está precalculado y es constante a propósito. Generarlo en cada
     * petición con `Hash::make` costaba un bcrypt entero, así que el camino
     * sin usuario ejecutaba dos y el camino con usuario uno: la mitigación
     * creaba, por tiempo, la misma señal que el mensaje idéntico borraba —un
     * correo inexistente tardaba el doble y quedaba distinguible por red.
     *
     * Es el hash de una cadena aleatoria de 32 bytes que ninguna contraseña
     * puede igualar. Su coste (12) es el de producción: si el proyecto
     * cambiara `bcrypt.rounds`, hay que regenerarlo con el nuevo coste para
     * que ambos caminos sigan tardando lo mismo.
     */
    private const HASH_SENUELO = '$2y$12$44M4ZMVaFI3jL4HgKj7xMeiIfbqw0wWPa8/HMT2H3T6Xm4LAz6D4e';

    /**
     * Un correo inexistente, uno inactivo y una contraseña equivocada
     * devuelven el mismo error. Además siempre se calcula un hash, aunque el
     * usuario no exista, para que el tiempo de respuesta tampoco delate cuál
     * de los tres fue.
     */
    public function autenticar(string $email, string $password): Usuario
    {
        $email = mb_strtolower(trim($email));

        $usuario = Usuario::query()->where('email', $email)->first();

        // Un solo Hash::check en ambos caminos, contra un señuelo constante:
        // ver HASH_SENUELO.
        $coincide = Hash::check($password, $usuario->password ?? self::HASH_SENUELO);

        if ($usuario === null || ! $coincide || ! $usuario->activo) {
            throw new ErrorDeDominio(
                CodigoDeError::CREDENCIALES_INVALIDAS,
                'El correo o la contraseña no son correctos.'
            );
        }

        return $usuario;
    }

    public function crear(DatosUsuario $datos): Usuario
    {
        $campos = $this->validar($this->normalizar($datos->todos()), [
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', 'string', 'in:'.implode(',', Usuario::ROLES)],
        ]);

        $this->garantizarCorreoLibre($campos['email']);

        return Usuario::query()->create($campos);
    }

    /**
     * Un campo ausente no cambia. La contraseña no se toca por esta vía, y un
     * administrador no puede quitarse su propio rol ni desactivarse: si
     * pudiera, el sistema quedaría sin nadie que lo administre.
     */
    public function actualizar(int $id, DatosUsuario $datos, ?Usuario $actor = null): Usuario
    {
        $usuario = Usuario::query()->find($id);

        if ($usuario === null) {
            throw new ErrorDeDominio(
                CodigoDeError::RECURSO_NO_ENCONTRADO,
                'El usuario indicado no existe.'
            );
        }

        $enviados = array_intersect_key(
            $datos->todos(),
            array_flip(['nombre', 'email', 'rol', 'activo'])
        );

        $campos = $this->validar($this->normalizar($enviados), [
            'nombre' => ['sometimes', 'required', 'string', 'min:3', 'max:120'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:150'],
            'rol' => ['sometimes', 'required', 'string', 'in:'.implode(',', Usuario::ROLES)],
            'activo' => ['sometimes', 'required', 'boolean'],
        ]);

        if (isset($campos['email'])) {
            $this->garantizarCorreoLibre($campos['email'], $id);
        }

        $campos = $this->castearBooleanos($campos);

        $this->impedirQueElActorSeInhabilite($usuario, $campos, $actor ?? Auth::user());

        $usuario->fill($campos)->save();

        return $usuario->refresh();
    }

    /** @return LengthAwarePaginator<int, Usuario> */
    public function listar(?string $buscar = null, int $pagina = 1): LengthAwarePaginator
    {
        return Usuario::query()
            ->when($buscar !== null && trim($buscar) !== '', function ($consulta) use ($buscar) {
                $texto = '%'.trim($buscar).'%';

                $consulta->where(function ($agrupada) use ($texto) {
                    $agrupada->where('nombre', 'ilike', $texto)
                        ->orWhere('email', 'ilike', $texto);
                });
            })
            ->orderBy('nombre')
            ->orderBy('id')
            ->paginate(self::POR_PAGINA, ['*'], 'pagina', max(1, $pagina));
    }

    /**
     * Convierte a booleano real lo que la validación ya aceptó como tal.
     *
     * La regla `boolean` de Laravel admite true, false, 1, 0, "1" y "0", pero
     * `validated()` devuelve el valor tal como llegó. Comparar eso contra
     * `false` estricto dejaba pasar `0` y `"0"` —justo lo que envía un
     * checkbox de un formulario— y con ello un administrador podía
     * desactivarse a sí mismo, que es la única puerta por la que el sistema
     * se queda sin nadie que lo administre.
     *
     * El casteo va después de validar, no antes: normalizar primero
     * ampliaría lo que se acepta y convertiría entradas inválidas en válidas
     * en silencio, contra RNF-010.
     *
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function castearBooleanos(array $campos): array
    {
        if (array_key_exists('activo', $campos)) {
            $campos['activo'] = filter_var($campos['activo'], FILTER_VALIDATE_BOOLEAN);
        }

        return $campos;
    }

    /**
     * Normaliza lo que el contrato declara normalizable, antes de validar:
     * de lo contrario un correo escrito con espacios se rechazaría por
     * formato en vez de recortarse, que es lo que el contrato promete.
     *
     * La contraseña nunca se toca — recortarla cambiaría el secreto.
     *
     * @param  array<string, mixed>  $campos
     * @return array<string, mixed>
     */
    private function normalizar(array $campos): array
    {
        if (isset($campos['email']) && is_string($campos['email'])) {
            $campos['email'] = mb_strtolower(trim($campos['email']));
        }

        if (isset($campos['nombre']) && is_string($campos['nombre'])) {
            $campos['nombre'] = (string) preg_replace('/\s+/u', ' ', trim($campos['nombre']));
        }

        return $campos;
    }

    /**
     * @param  array<string, mixed>  $campos
     * @param  array<string, array<int, mixed>>  $reglas
     * @return array<string, mixed>
     */
    private function validar(array $campos, array $reglas): array
    {
        return ValidadorDeDominio::validar($campos, $reglas);
    }

    /**
     * El correo repetido se comprueba acá y no con una regla `unique`, igual
     * que el documento del cliente y el nombre de la categoría.
     *
     * El motivo es que un correo repetido y un correo mal escrito son
     * problemas distintos y quien los recibe necesita distinguirlos: el código
     * de un duplicado nombra la entidad, y solo este punto sabe que el campo
     * es de un usuario.
     *
     * `DOCUMENTO_DUPLICADO` está mal nombrado para un correo —no hay ningún
     * documento de por medio—, pero renombrarlo rompe el contrato con quien ya
     * lo consume, así que se conserva. Lo que cambió no es el código: cambió
     * que sea una decisión escrita en vez de un valor por defecto que nadie
     * eligió.
     *
     * La restricción de la base sigue siendo la garantía: esta comprobación da
     * el mensaje correcto, no la exclusividad.
     */
    private function garantizarCorreoLibre(string $email, ?int $exceptoId = null): void
    {
        $existe = Usuario::query()
            ->where('email', $email)
            ->when($exceptoId !== null, fn ($consulta) => $consulta->whereKeyNot($exceptoId))
            ->exists();

        if ($existe) {
            throw new ErrorDeDominio(
                CodigoDeError::DOCUMENTO_DUPLICADO,
                'Ya existe un usuario con ese correo.',
                ['campo' => 'email']
            );
        }
    }

    /** @param  array<string, mixed>  $campos */
    private function impedirQueElActorSeInhabilite(Usuario $usuario, array $campos, ?Usuario $actor): void
    {
        if ($actor === null || $actor->id !== $usuario->id || ! $usuario->esAdministrador()) {
            return;
        }

        $sePierdeElRol = isset($campos['rol']) && $campos['rol'] !== Usuario::ROL_ADMINISTRADOR;
        $seDesactiva = isset($campos['activo']) && $campos['activo'] === false;

        if ($sePierdeElRol || $seDesactiva) {
            throw new ErrorDeDominio(
                CodigoDeError::NO_AUTORIZADO,
                'Un administrador no puede quitarse su propio rol ni desactivarse.'
            );
        }
    }
}
