<?php

namespace Tests\Feature\Compartido;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Errores\ValidadorDeDominio;
use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El código de un duplicado nombra la entidad, y el validador compartido no
 * sabe de qué entidad es el campo que recibe. Por eso no elige ninguno.
 */
final class CodigoDeUnicidadTest extends TestCase
{
    use RefreshDatabase;

    private UsuarioService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new UsuarioService;
    }

    /**
     * El valor por defecto respondía `DOCUMENTO_DUPLICADO` a cualquier `unique`
     * de cualquier dominio, sin que nadie lo eligiera. Ahora un `unique` sin
     * código declarado cae al genérico de formato, que para un duplicado es
     * visiblemente incorrecto: quien agregue uno nuevo se encuentra con un
     * mensaje que no corresponde mientras escribe la regla, en vez de
     * descubrirlo en producción.
     */
    public function test_el_validador_compartido_no_elige_codigo_de_unicidad(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe']);

        try {
            ValidadorDeDominio::validar(
                ['email' => 'ana@ejemplo.pe'],
                ['email' => ['required', 'email', 'unique:usuarios,email']]
            );
            $this->fail('Se aceptó un valor duplicado.');
        } catch (ErrorDeDominio $error) {
            $this->assertNotSame(
                CodigoDeError::DOCUMENTO_DUPLICADO,
                $error->codigo,
                'El validador compartido no puede elegir un código de unicidad: no sabe de qué entidad es el campo.'
            );
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }
    }

    /** El correo repetido sí responde con su código, declarado donde se sabe la entidad. */
    public function test_un_correo_repetido_responde_con_su_codigo(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe']);

        $error = $this->errorAlCrear('ana@ejemplo.pe');

        $this->assertSame(CodigoDeError::DOCUMENTO_DUPLICADO, $error->codigo);
        $this->assertSame('email', $error->detalle['campo'] ?? null);
    }

    /**
     * Un correo repetido y uno mal escrito son problemas distintos, y quien los
     * recibe necesita distinguirlos para saber qué decirle a quien los escribe.
     *
     * Se fija porque la forma obvia de declarar el código —asociarlo al campo
     * `email` en el validador— los confunde: ese mecanismo alcanza a cualquier
     * fallo del campo, no a la regla de unicidad, así que un correo mal escrito
     * respondería «ya existe». Por eso la comprobación va aparte.
     */
    public function test_un_correo_mal_escrito_no_responde_como_duplicado(): void
    {
        $error = $this->errorAlCrear('no-es-un-correo');

        $this->assertNotSame(
            CodigoDeError::DOCUMENTO_DUPLICADO,
            $error->codigo,
            'Un correo mal escrito no es un correo repetido.'
        );
        $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
    }

    /**
     * `UsuarioService` traía su propia copia de la traducción, escrita antes de
     * que existiera la compartida, y las dos ya habían divergido: la compartida
     * trataba `between` y la copia no.
     *
     * Sin `between` en ninguna regla de usuarios la diferencia no tenía efecto
     * todavía. Se fija que la traducción compartida lo trate, porque es la que
     * quedó como única.
     */
    public function test_la_traduccion_compartida_trata_between(): void
    {
        try {
            ValidadorDeDominio::validar(['nombre' => 'a'], ['nombre' => ['required', 'between:3,10']]);
            $this->fail('Se aceptó un valor fuera de rango.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
        }
    }

    private function errorAlCrear(string $email): ErrorDeDominio
    {
        try {
            $this->servicio->crear(DatosUsuario::desde([
                'nombre' => 'Ana Quispe',
                'email' => $email,
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ]));
        } catch (ErrorDeDominio $error) {
            return $error;
        }

        $this->fail("Se aceptó el correo «{$email}».");
    }
}
