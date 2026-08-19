<?php

namespace Tests\Feature\Dominios\Usuarios;

use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Usuarios\Datos\DatosUsuario;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Usuarios\Servicios\UsuarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class UsuarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private UsuarioService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new UsuarioService;
    }

    // --- Autenticación (RF-001) ---

    public function test_autentica_con_credenciales_validas(): void
    {
        $usuario = Usuario::factory()->create([
            'email' => 'ana@ejemplo.pe',
            'password' => 'contrasena-valida',
        ]);

        $this->assertSame(
            $usuario->id,
            $this->servicio->autenticar('ana@ejemplo.pe', 'contrasena-valida')->id
        );
    }

    public function test_el_correo_se_normaliza_antes_de_comparar(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->assertNotNull($this->servicio->autenticar('  ANA@Ejemplo.PE  ', 'contrasena-valida'));
    }

    /**
     * El contrato exige que los tres casos sean indistinguibles: si el error
     * cambiara según la causa, serviría para averiguar qué correos existen.
     */
    public function test_contrasena_incorrecta_usuario_inexistente_e_inactivo_dan_el_mismo_error(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);
        Usuario::factory()->inactivo()->create(['email' => 'baja@ejemplo.pe', 'password' => 'contrasena-valida']);

        $mensajes = [];

        foreach ([
            ['ana@ejemplo.pe', 'contrasena-equivocada'],
            ['nadie@ejemplo.pe', 'contrasena-valida'],
            ['baja@ejemplo.pe', 'contrasena-valida'],
        ] as [$email, $password]) {
            try {
                $this->servicio->autenticar($email, $password);
                $this->fail("Se autenticó con {$email}, y no debía.");
            } catch (ErrorDeDominio $error) {
                $this->assertSame(CodigoDeError::CREDENCIALES_INVALIDAS, $error->codigo);
                $mensajes[] = $error->getMessage();
            }
        }

        $this->assertCount(1, array_unique($mensajes), 'Los tres casos deben devolver el mismo mensaje.');
    }

    // --- Alta (RF-002) ---

    public function test_crea_un_usuario_guardando_solo_el_hash_de_la_contrasena(): void
    {
        $usuario = $this->servicio->crear(DatosUsuario::desde([
            'nombre' => 'Ana  Quispe',
            'email' => '  ANA@Ejemplo.PE ',
            'password' => 'contrasena-valida',
            'rol' => Usuario::ROL_ADMINISTRADOR,
        ]));

        $this->assertSame('Ana Quispe', $usuario->nombre);
        $this->assertSame('ana@ejemplo.pe', $usuario->email);
        $this->assertNotSame('contrasena-valida', $usuario->password);
        $this->assertTrue(Hash::check('contrasena-valida', $usuario->password));
        $this->assertArrayNotHasKey('password', $usuario->toArray());
    }

    public static function entradasInvalidas(): array
    {
        return [
            'sin nombre' => [['email' => 'a@b.pe', 'password' => 'contrasena', 'rol' => 'vendedor'], CodigoDeError::CAMPO_REQUERIDO],
            'nombre corto' => [['nombre' => 'An', 'email' => 'a@b.pe', 'password' => 'contrasena', 'rol' => 'vendedor'], CodigoDeError::CAMPO_FUERA_DE_RANGO],
            'correo inválido' => [['nombre' => 'Ana Q', 'email' => 'no-es-correo', 'password' => 'contrasena', 'rol' => 'vendedor'], CodigoDeError::CAMPO_FORMATO_INVALIDO],
            'contraseña corta' => [['nombre' => 'Ana Q', 'email' => 'a@b.pe', 'password' => 'corta', 'rol' => 'vendedor'], CodigoDeError::CAMPO_FUERA_DE_RANGO],
            'rol inventado' => [['nombre' => 'Ana Q', 'email' => 'a@b.pe', 'password' => 'contrasena', 'rol' => 'supervisor'], CodigoDeError::CAMPO_FORMATO_INVALIDO],
        ];
    }

    /** @param  array<string, mixed>  $campos */
    #[DataProvider('entradasInvalidas')]
    public function test_rechaza_la_entrada_invalida_con_su_codigo(array $campos, CodigoDeError $esperado): void
    {
        try {
            $this->servicio->crear(DatosUsuario::desde($campos));
            $this->fail('Se aceptó una entrada que el contrato rechaza.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame($esperado, $error->codigo);
        }
    }

    public function test_rechaza_un_correo_ya_registrado(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe']);

        try {
            $this->servicio->crear(DatosUsuario::desde([
                'nombre' => 'Otra Ana',
                'email' => 'ana@ejemplo.pe',
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ]));
            $this->fail('Se aceptó un correo duplicado.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_DUPLICADO, $error->codigo);
        }
    }

    // --- Edición (RF-002) ---

    public function test_un_campo_ausente_no_cambia(): void
    {
        $usuario = Usuario::factory()->create(['nombre' => 'Ana Quispe', 'rol' => Usuario::ROL_VENDEDOR]);

        $actualizado = $this->servicio->actualizar($usuario->id, DatosUsuario::desde(['nombre' => 'Ana Quispe Rojas']));

        $this->assertSame('Ana Quispe Rojas', $actualizado->nombre);
        $this->assertSame(Usuario::ROL_VENDEDOR, $actualizado->rol);
    }

    public function test_la_contrasena_no_se_cambia_por_esta_via(): void
    {
        $usuario = Usuario::factory()->create(['password' => 'contrasena-original']);

        $this->servicio->actualizar($usuario->id, DatosUsuario::desde([
            'nombre' => 'Nombre Nuevo',
            'password' => 'contrasena-colada',
        ]));

        $this->assertTrue(Hash::check('contrasena-original', $usuario->refresh()->password));
    }

    public function test_un_administrador_no_puede_quitarse_su_propio_rol(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        try {
            $this->servicio->actualizar($admin->id, DatosUsuario::desde(['rol' => Usuario::ROL_VENDEDOR]), $admin);
            $this->fail('Un administrador se quitó su propio rol.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::NO_AUTORIZADO, $error->codigo);
        }

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $admin->refresh()->rol);
    }

    public function test_un_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        try {
            $this->servicio->actualizar($admin->id, DatosUsuario::desde(['activo' => false]), $admin);
            $this->fail('Un administrador se desactivó a sí mismo.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::NO_AUTORIZADO, $error->codigo);
        }

        $this->assertTrue($admin->refresh()->activo);
    }

    public function test_un_administrador_si_puede_desactivar_a_otro(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $otro = Usuario::factory()->administrador()->create();

        $this->assertFalse(
            $this->servicio->actualizar($otro->id, DatosUsuario::desde(['activo' => false]), $admin)->activo
        );
    }

    public function test_editar_un_usuario_inexistente_no_lo_encuentra(): void
    {
        try {
            $this->servicio->actualizar(9999, DatosUsuario::desde(['nombre' => 'Quien Sea']));
            $this->fail('Se editó un usuario que no existe.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::RECURSO_NO_ENCONTRADO, $error->codigo);
        }
    }

    // --- Listado (RF-002) ---

    public function test_lista_paginado_de_veinte_ordenado_por_nombre_y_sin_contrasenas(): void
    {
        Usuario::factory()->count(25)->create();

        $pagina = $this->servicio->listar();

        $this->assertSame(20, $pagina->perPage());
        $this->assertCount(20, $pagina->items());
        $this->assertSame(25, $pagina->total());

        $nombres = array_map(fn (Usuario $u) => $u->nombre, $pagina->items());
        $ordenados = $nombres;
        sort($ordenados, SORT_STRING);
        $this->assertSame($ordenados, $nombres);

        $this->assertArrayNotHasKey('password', $pagina->items()[0]->toArray());
    }

    public function test_la_busqueda_filtra_por_nombre_o_correo(): void
    {
        Usuario::factory()->create(['nombre' => 'Ana Quispe', 'email' => 'ana@ejemplo.pe']);
        Usuario::factory()->create(['nombre' => 'Luis Rojas', 'email' => 'luis@ejemplo.pe']);

        $this->assertCount(1, $this->servicio->listar('quispe')->items());
        $this->assertCount(1, $this->servicio->listar('luis@')->items());
    }

    /** RNF-011: el texto de búsqueda no se concatena en la consulta. */
    public function test_la_busqueda_no_es_vulnerable_a_inyeccion(): void
    {
        Usuario::factory()->create(['nombre' => 'Ana Quispe']);

        $pagina = $this->servicio->listar("' or 1=1 --");

        $this->assertCount(0, $pagina->items());
        $this->assertSame(1, Usuario::query()->count());
    }
}
