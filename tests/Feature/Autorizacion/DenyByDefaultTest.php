<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * RNF-013. Lo que se comprueba acá no es que las rutas conocidas funcionen,
 * sino que una ruta que nadie autorizó se rechace sola: agregar una ruta y
 * olvidar su permiso tiene que fallar de forma visible, no quedar abierta.
 */
final class DenyByDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ruta que existe pero que ningún documento autoriza. Se registra
        // acá, y no en el archivo de rutas, justamente porque no debe existir
        // en la aplicación real.
        Route::middleware('web')->get('/ruta-sin-regla-declarada', fn () => 'no debería verse');
    }

    public function test_una_ruta_sin_regla_declarada_rechaza_aunque_sea_administrador(): void
    {
        $respuesta = $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson('/ruta-sin-regla-declarada');

        $respuesta->assertStatus(403);
        $respuesta->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    public function test_una_ruta_sin_regla_declarada_rechaza_sin_sesion(): void
    {
        $this->getJson('/ruta-sin-regla-declarada')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    public function test_el_health_check_responde_sin_sesion(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_la_raiz_responde_sin_sesion(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_un_usuario_desactivado_pierde_el_acceso_aunque_tenga_sesion(): void
    {
        $usuario = Usuario::factory()->administrador()->create();

        $this->actingAs($usuario);

        $usuario->update(['activo' => false]);

        $this->getJson('/ruta-sin-regla-declarada')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    public function test_el_error_no_expone_nada_interno(): void
    {
        $cuerpo = $this->getJson('/ruta-sin-regla-declarada')->json();

        $this->assertSame(['codigo', 'mensaje', 'detalle'], array_keys($cuerpo['error']));
        $this->assertStringNotContainsString('/', $cuerpo['error']['mensaje']);
        $this->assertStringNotContainsString('Exception', json_encode($cuerpo));
    }
}
