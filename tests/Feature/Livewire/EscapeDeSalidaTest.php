<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Usuarios\Livewire\ListaDeUsuarios;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Ninguna vista de este frente desactiva el escape, por ninguna de las vías
     * que existen para hacerlo.
     *
     * `{!! !!}` es la más conocida, pero no la única: `HtmlString` y `->toHtml()`
     * marcan una cadena como segura, `@php echo` y `html_entity_decode`
     * esquivan a Blade por completo, y `Js::from`/`@js(` inyectan en contexto
     * JavaScript, donde el escape de HTML no protege. Barrer solo la primera
     * dejaría las otras cinco sin cubrir el día que alguien las use.
     *
     * Vías sugeridas por QA durante la validación, a partir de su propio
     * barrido.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function viasQueDesactivanElEscape(): array
    {
        return [
            'echo sin escapar' => ['/\{!!.*!!\}/s', '{!! !!}'],
            'cadena marcada como segura mediante HtmlString' => ['/\bHtmlString\b/', 'HtmlString'],
            'cadena marcada como segura con toHtml' => ['/->toHtml\(/', '->toHtml()'],
            'salida cruda esquivando a Blade' => ['/@php\s+echo\b/s', '@php echo'],
            'decodificacion de entidades' => ['/\bhtml_entity_decode\s*\(/', 'html_entity_decode()'],
            'inyeccion en contexto JavaScript' => ['/\bJs::from\s*\(|@js\s*\(/', 'Js::from() o @js()'],
        ];
    }

    #[DataProvider('viasQueDesactivanElEscape')]
    public function test_ninguna_vista_desactiva_el_escape(string $patron, string $via): void
    {
        $culpables = [];

        $directorio = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        foreach ($directorio as $archivo) {
            if (! $archivo->isFile() || ! str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            if (preg_match($patron, (string) file_get_contents($archivo->getPathname()))) {
                $culpables[] = str_replace(resource_path('views/'), '', $archivo->getPathname());
            }
        }

        $this->assertSame([], $culpables, "Estas vistas desactivan el escape con {$via}.");
    }
}
