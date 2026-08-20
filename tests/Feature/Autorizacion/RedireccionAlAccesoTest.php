<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Regla transversal de docs/frontend/experiencia.md: quien pide algo sin
 * sesión llega al acceso y vuelve a donde iba después de identificarse.
 *
 * Un 401 de texto plano es la respuesta correcta para quien consume datos, y
 * un callejón sin salida para quien navegaba a una pantalla: recibe un mensaje
 * suelto, sin forma de llegar al acceso ni de volver.
 */
final class RedireccionAlAccesoTest extends TestCase
{
    use RefreshDatabase;

    public static function pantallasProtegidas(): array
    {
        return [
            'catálogo de productos' => ['/productos'],
            'categorías' => ['/categorias'],
        ];
    }

    #[DataProvider('pantallasProtegidas')]
    public function test_una_peticion_de_pantalla_sin_sesion_va_al_acceso(string $ruta): void
    {
        $this->get($ruta)->assertRedirect('/login');
    }

    #[DataProvider('pantallasProtegidas')]
    public function test_la_url_pedida_se_conserva_para_volver_despues(string $ruta): void
    {
        $this->get($ruta);

        $this->assertSame(
            url($ruta),
            session('url.intended'),
            'Sin esto, quien inicia sesión aterriza en el panel y pierde lo que estaba pidiendo.'
        );
    }

    /** Quien consume datos sigue recibiendo el código de la taxonomía. */
    #[DataProvider('pantallasProtegidas')]
    public function test_una_peticion_de_datos_sin_sesion_sigue_recibiendo_401(string $ruta): void
    {
        $this->getJson($ruta)
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');

        $this->assertNull(session('url.intended'), 'Un cliente de datos no vuelve a ninguna pantalla.');
    }

    /**
     * Livewire recibe 401 y no un redirect: el componente no sabría qué hacer
     * con una redirección en respuesta a una interacción.
     */
    public function test_livewire_sin_sesion_recibe_401_y_no_un_redirect(): void
    {
        $this->post(EndpointResolver::prefix().'/update', ['components' => []], ['X-Livewire' => 'true'])
            ->assertStatus(401);
    }

    /** La consulta que acompaña a la URL también se conserva. */
    public function test_se_conserva_tambien_la_consulta_de_la_url(): void
    {
        $this->get('/productos?buscar=arroz&pagina=2');

        $this->assertSame(url('/productos?buscar=arroz&pagina=2'), session('url.intended'));
    }

    public function test_con_sesion_no_hay_redireccion(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->get('/productos')
            ->assertOk();

        $this->assertNull(session('url.intended'));
    }

    /** Un usuario desactivado en pleno uso también vuelve al acceso. */
    public function test_un_usuario_desactivado_es_enviado_al_acceso(): void
    {
        $usuario = Usuario::factory()->administrador()->create();
        $this->actingAs($usuario);
        $usuario->update(['activo' => false]);

        $this->get('/productos')->assertRedirect('/login');
    }
}
