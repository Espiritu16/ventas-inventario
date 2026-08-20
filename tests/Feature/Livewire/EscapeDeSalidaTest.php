<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Usuarios\Livewire\ListaDeUsuarios;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-04 (RNF-012). Que el contenido que escribe una persona se renderice como
 * texto y no se ejecute.
 *
 * Se comprueba de dos maneras a propósito. Por comportamiento, que es lo que
 * de verdad importa; y por estructura, buscando `{!! !!}` en las vistas,
 * porque el escape de Blade es correcto por omisión y la única forma de
 * perderlo es desactivarlo explícitamente. La prueba de comportamiento sola
 * pasaría el día que alguien lo desactive en una vista que ninguna prueba
 * recorre.
 */
final class EscapeDeSalidaTest extends TestCase
{
    use RefreshDatabase;

    private const CARGA = '<script>alert("x")</script>';

    public function test_un_nombre_con_contenido_malicioso_se_muestra_escapado(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        Usuario::factory()->create(['nombre' => self::CARGA]);

        $html = $this->get('/usuarios')->getContent();

        $this->assertStringNotContainsString(self::CARGA, $html, 'El script no puede llegar crudo al navegador.');
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_tambien_se_escapa_al_renderizar_el_componente(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
        Usuario::factory()->create(['nombre' => self::CARGA]);

        $html = Livewire::test(ListaDeUsuarios::class)->html();

        $this->assertStringNotContainsString(self::CARGA, $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** El nombre de quien tiene la sesión también pasa por el encabezado. */
    public function test_el_encabezado_escapa_el_nombre_de_la_sesion(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create(['nombre' => self::CARGA]));

        $html = Blade::render('<x-layout>contenido</x-layout>');

        $this->assertStringNotContainsString(self::CARGA, $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /** Un mensaje de error tampoco puede convertirse en marcado. */
    public function test_el_aviso_escapa_su_contenido(): void
    {
        $html = Blade::render('<x-aviso tipo="error">{{ $texto }}</x-aviso>', ['texto' => self::CARGA]);

        $this->assertStringNotContainsString(self::CARGA, $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * Ninguna vista de este frente desactiva el escape. Si alguna necesitara
     * hacerlo alguna vez, tendría que justificarse acá y no pasar inadvertida.
     */
    public function test_ninguna_vista_desactiva_el_escape_de_blade(): void
    {
        $culpables = [];

        $directorio = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($directorio as $archivo) {
            if (! $archivo->isFile() || ! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            if (preg_match('/\{!!.*!!\}/s', (string) file_get_contents($archivo->getPathname()))) {
                $culpables[] = str_replace(resource_path('views/'), '', $archivo->getPathname());
            }
        }

        $this->assertSame([], $culpables, 'Estas vistas desactivan el escape de Blade con {!! !!}.');
    }
}
