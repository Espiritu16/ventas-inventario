<?php

namespace Tests\Feature\Dominios\Usuarios;

use App\Compartido\Autorizacion\Permiso;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Usuarios\Livewire\HumoDeInstalacion;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

/** Declara permiso de clase; solo el administrador puede verlo. */
#[Permiso('GET /usuarios')]
class ComponenteDeclarado extends Component
{
    public int $veces = 0;

    public function tocar(): void
    {
        $this->veces++;
    }

    #[Permiso('POST /usuarios')]
    public function escribir(): void
    {
        $this->veces += 10;
    }

    public function render(): string
    {
        return '<div>declarado {{ $veces }}</div>';
    }
}

/** No declara nada: debe fallar siempre. */
class ComponenteSinDeclarar extends Component
{
    public function render(): string
    {
        return '<div>sin declarar</div>';
    }
}

/**
 * El mecanismo de permisos en componentes.
 *
 * Proteger la ruta de una pantalla no protege sus componentes: sus métodos
 * viajan por el endpoint de Livewire, que exige sesión pero no distingue rol.
 * Y comprobar al montar tampoco alcanza, porque en cada interacción el
 * componente se hidrata desde el snapshot y `render()` vuelve a servir datos
 * sin pasar por el montaje.
 *
 * Las comprobaciones por HTTP no son un lujo: por componente, un rechazo que
 * nace en `render()` llega envuelto en ViewException, y solo por HTTP se ve
 * qué recibe de verdad quien está del otro lado.
 */
final class PermisosEnComponentesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Livewire::component('prueba.declarado', ComponenteDeclarado::class);
        Livewire::component('prueba.sin-declarar', ComponenteSinDeclarar::class);
    }

    private function actualizarPorHttp(Usuario $como, string $componente, array $calls = []): TestResponse
    {
        // El snapshot se obtiene con quien sí tiene permiso: reproduce el
        // ataque real, que no es fabricar un snapshot sino reusar uno legítimo
        // con otra sesión.
        $snapshot = Livewire::actingAs(Usuario::factory()->administrador()->create())
            ->test($componente)->snapshot;

        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->actingAs($como);

        return $this->postJson(EndpointResolver::prefix().'/update', [
            'components' => [[
                'snapshot' => json_encode($snapshot),
                'updates' => [],
                'calls' => $calls,
            ]],
        ], ['X-Livewire' => 'true']);
    }

    public function test_el_administrador_puede_usar_el_componente(): void
    {
        Livewire::actingAs(Usuario::factory()->administrador()->create())
            ->test('prueba.declarado')
            ->call('tocar')
            ->assertSet('veces', 1);
    }

    /**
     * El agujero que QA encontró en S-01-F: la escritura estaba protegida y la
     * lectura no. Sin `calls`, el componente solo se hidrata y re-renderiza.
     */
    public function test_la_lectura_por_hidratacion_tambien_se_rechaza(): void
    {
        $respuesta = $this->actualizarPorHttp(Usuario::factory()->create(), 'prueba.declarado');

        $respuesta->assertStatus(403)->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
        $respuesta->assertJsonPath('error.detalle.motivo', 'rol_sin_permiso');
    }

    public function test_la_escritura_se_rechaza(): void
    {
        $this->actualizarPorHttp(Usuario::factory()->create(), 'prueba.declarado', [
            ['method' => 'escribir', 'params' => []],
        ])->assertStatus(403)->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    /** Un método sin atributo propio hereda el de la pantalla. */
    public function test_un_metodo_sin_atributo_hereda_el_permiso_de_la_clase(): void
    {
        $this->actualizarPorHttp(Usuario::factory()->create(), 'prueba.declarado', [
            ['method' => 'tocar', 'params' => []],
        ])->assertStatus(403);
    }

    /**
     * Deny-by-default: no declarar no es quedar abierto.
     *
     * Falla incluso para el administrador y ya al montarse, que es lo más
     * fuerte que puede pasar: un componente sin declaración no llega a
     * servirle datos a nadie, así que el olvido se descubre la primera vez que
     * alguien abre la pantalla y no cuando aparece quien no debía verla.
     */
    public function test_un_componente_que_no_declara_permiso_falla_para_todos(): void
    {
        $error = $this->errorAlMontar('prueba.sin-declarar', Usuario::factory()->administrador()->create());

        $this->assertSame(CodigoDeError::NO_AUTORIZADO, $error->codigo);
        $this->assertSame('componente_sin_permiso_declarado', $error->detalle['motivo']);
    }

    /**
     * Hacia afuera los dos rechazos son idénticos —distinguirlos filtraría
     * información—; hacia adentro el detalle los separa, porque buscar el
     * problema en la matriz cuando lo que falta es el atributo es perder el
     * tiempo donde no está.
     */
    public function test_los_dos_rechazos_son_indistinguibles_para_quien_los_recibe(): void
    {
        $porRol = $this->errorAlMontar('prueba.declarado', Usuario::factory()->create());
        $porFalta = $this->errorAlMontar('prueba.sin-declarar', Usuario::factory()->administrador()->create());

        $this->assertSame($porRol->codigo, $porFalta->codigo);
        $this->assertSame($porRol->getMessage(), $porFalta->getMessage());
        $this->assertSame($porRol->status(), $porFalta->status());

        $this->assertNotSame($porRol->detalle['motivo'], $porFalta->detalle['motivo']);
    }

    /**
     * Desenvuelve el rechazo del ViewException en que Livewire lo envuelve
     * cuando nace en `render()`. Por HTTP no hace falta —Laravel lo desenvuelve
     * y el cliente recibe 403 con su código, verificado arriba—; en una prueba
     * de componente, sí.
     */
    private function errorAlMontar(string $componente, Usuario $como): ErrorDeDominio
    {
        try {
            Livewire::actingAs($como)->test($componente);
            $this->fail("El componente {$componente} no rechazó a quien no debía usarlo.");
        } catch (\Throwable $error) {
            while ($error !== null && ! $error instanceof ErrorDeDominio) {
                $error = $error->getPrevious();
            }

            $this->assertInstanceOf(ErrorDeDominio::class, $error, 'El rechazo no es un error de dominio.');

            return $error;
        }
    }

    /** El componente de humo ya declara el suyo y sigue funcionando. */
    public function test_el_componente_de_humo_declara_su_permiso(): void
    {
        Livewire::actingAs(Usuario::factory()->create())
            ->test(HumoDeInstalacion::class)
            ->call('interactuar')
            ->assertSet('interacciones', 1);
    }
}
