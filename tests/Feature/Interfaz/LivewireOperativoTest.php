<?php

namespace Tests\Feature\Interfaz;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Dominios\Usuarios\Livewire\HumoDeInstalacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

/**
 * UT-06. Lo que se comprueba es que Livewire quedó operativo sobre lo que
 * dejó S-00: que un componente ubicado dentro de su dominio se descubre, que
 * renderiza dentro del layout base, y que una interacción cambia el estado
 * sin recargar la página.
 *
 * La vista que lo monta vive en tests/, no en resources/views/: esa carpeta
 * es del frente de interfaz, y una prueba del backend no tiene por qué
 * dejarle archivos ahí.
 */
final class LivewireOperativoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(__DIR__.'/../../recursos/vistas');
    }

    public function test_el_componente_se_descubre_dentro_de_su_dominio(): void
    {
        Livewire::test('usuarios.humo-de-instalacion')->assertOk();
    }

    public function test_renderiza_dentro_del_layout_base(): void
    {
        $html = view('pagina-de-humo')->render();

        $this->assertStringContainsString('<html lang="es">', $html);
        $this->assertStringContainsString('Livewire operativo', $html);
        $this->assertStringContainsString('/build/assets/', $html, 'El layout debe seguir sirviendo los assets de Vite.');
    }

    public function test_una_interaccion_actualiza_el_estado_sin_recargar(): void
    {
        Livewire::test(HumoDeInstalacion::class)
            ->assertSet('interacciones', 0)
            ->assertSee('Interacciones')
            ->call('interactuar')
            ->assertSet('interacciones', 1)
            ->call('interactuar')
            ->assertSet('interacciones', 2);
    }

    /**
     * Los assets que el paquete registra hoy: el script y sus dos mapas de
     * origen. El conjunto exacto depende de la versión de Livewire, y por eso
     * la declaración va por patrón y no enumerada — la prueba comprueba que
     * todos los que existen se sirven, sin fijar cuáles son.
     */
    public function test_todos_los_assets_registrados_por_livewire_se_sirven(): void
    {
        $prefijo = EndpointResolver::prefix();

        $assets = collect(app('router')->getRoutes())
            ->filter(fn ($ruta) => str_starts_with('/'.$ruta->uri(), $prefijo))
            ->filter(fn ($ruta) => in_array('GET', $ruta->methods(), true))
            ->filter(fn ($ruta) => in_array(pathinfo($ruta->uri(), PATHINFO_EXTENSION), ['js', 'css', 'map'], true))
            ->reject(fn ($ruta) => str_contains($ruta->uri(), '{'))
            ->map(fn ($ruta) => '/'.$ruta->uri());

        $this->assertNotEmpty($assets, 'Livewire debería registrar al menos un asset estático.');

        foreach ($assets as $asset) {
            $this->get($asset)->assertOk();
        }
    }

    /**
     * El patrón de assets tiene que ser estrecho: si cubriera cualquier cosa
     * bajo el prefijo, abriría por la ventana lo que el deny-by-default
     * cierra por la puerta.
     */
    public function test_el_patron_de_assets_no_cubre_nada_que_no_sea_un_asset(): void
    {
        $prefijo = EndpointResolver::prefix();

        foreach ([
            'POST '.$prefijo.'/livewire.js',   // mismo archivo, otro método
            'GET '.$prefijo.'/update',
            'GET '.$prefijo.'/upload-file',
            'GET '.$prefijo.'/preview-file/x.pdf',
            'GET /livewire.js',                // fuera del prefijo
        ] as $identificador) {
            $this->assertFalse(
                MatrizDePermisos::seSirveSinSesion($identificador),
                "El patrón de assets no debería cubrir «{$identificador}»."
            );
        }
    }
}
