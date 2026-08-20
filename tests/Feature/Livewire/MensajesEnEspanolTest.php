<?php

namespace Tests\Feature\Livewire;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Dominios\Catalogo\Livewire\ListaDeCategorias;
use App\Dominios\Catalogo\Livewire\ListaDeProductos;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Clientes\Livewire\ListaDeClientes;
use App\Dominios\Proveedores\Livewire\ListaDeProveedores;
use App\Dominios\Usuarios\Livewire\ListaDeUsuarios;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Que el mensaje que lee quien opera llegue en español a la pantalla.
 *
 * **Mira el texto que queda en la pantalla, no el archivo de traducciones.**
 * Un archivo de traducciones puede existir, estar completo y coherente, y no
 * estar aplicándose: desde el lado del código las dos situaciones se ven
 * iguales, y las pruebas que leen el archivo pasan en las dos. La única forma
 * honesta de comprobarlo es provocar rechazos reales y mirar lo que queda en
 * `errorDeCampo`, que es literalmente lo que la persona ve junto al campo.
 *
 * Por eso vive acá y no del lado del dominio: la afirmación es sobre la
 * pantalla.
 *
 * Alcance: **solo el tramo genérico del validador**. Los mensajes que escriben
 * los servicios —"Ya existe un producto con ese código"— ya están en español y
 * no los toca nadie; el problema era el tramo que Laravel traduce por su
 * cuenta, que salía en inglés.
 *
 * Las cuatro mutaciones que tiene que detectar, y por qué cada una:
 *
 * 1. el idioma de la aplicación en inglés
 * 2. el archivo de traducciones ausente
 * 3. el mapa de nombres vacío
 * 4. **un solo nombre legible faltante**
 *
 * La cuarta es la que separa esta prueba de una decorativa. Con el mapa
 * completo el mensaje dice "el campo precio al por menor"; sin esa entrada
 * dice "el campo precio_menor", que sigue siendo español y sigue teniendo
 * sentido gramatical. Una prueba que solo comprobara "salió algo en español"
 * pasaría igual. Por eso se afirma el nombre legible **de cada campo**, y se
 * eligen campos cuyo nombre legible difiere del técnico: donde `nombre` se
 * traduce a `nombre`, la mutación sería indetectable y esa fila no aportaría.
 */
final class MensajesEnEspanolTest extends TestCase
{
    use RefreshDatabase;

    /** Marcas de que el texto quedó en inglés, sea por idioma o por archivo ausente. */
    private const MARCAS_EN_INGLES = '/\b(The|field|must|invalid|format|characters|between)\b/';

    private function comoAdministrador(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
    }

    /**
     * Rechazos reales, cada uno provocado desde su pantalla.
     *
     * Se eligen los que caen al validador genérico, que es la superficie que
     * este cambio corrige. Cada uno declara el campo que debe quedar señalado
     * y el nombre legible que el mensaje debe contener.
     *
     * @return array<string, array{campo: string, legible: string, texto: string}>
     */
    private function rechazosDeLaInterfaz(): array
    {
        $categoria = Categoria::factory()->create();

        $recoger = fn ($componente) => [
            'campo' => $componente->get('campoConError'),
            'texto' => (string) $componente->get('errorDeCampo'),
        ];

        return [
            'código de producto con un espacio' => [
                'campo' => 'codigo',
                'legible' => 'código',
                ...$recoger(Livewire::test(ListaDeProductos::class)->call('nuevo')
                    ->set('codigo', 'ARROZ 01')->set('nombre', 'Arroz extra')
                    ->set('categoriaDelFormulario', $categoria->id)->set('unidadMedida', 'KGM')
                    ->set('precioMenor', '10.00')->set('precioMayor', '8.00')->call('crear')),
            ],
            'precio de producto que no es un número' => [
                'campo' => 'precio_menor',
                'legible' => 'precio al por menor',
                ...$recoger(Livewire::test(ListaDeProductos::class)->call('nuevo')
                    ->set('codigo', 'ARROZ01')->set('nombre', 'Arroz extra')
                    ->set('categoriaDelFormulario', $categoria->id)->set('unidadMedida', 'KGM')
                    ->set('precioMenor', 'diez')->set('precioMayor', '8.00')->call('crear')),
            ],
            'razón social de proveedor demasiado corta' => [
                'campo' => 'razon_social',
                'legible' => 'razón social',
                ...$recoger(Livewire::test(ListaDeProveedores::class)->call('nuevo')
                    ->set('numeroDocumento', '20123456789')->set('razonSocial', 'X')->call('crear')),
            ],
            'correo de usuario con formato inválido' => [
                'campo' => 'email',
                'legible' => 'correo',
                ...$recoger(Livewire::test(ListaDeUsuarios::class)->call('nuevo')
                    ->set('nombre', 'Ana Quispe')->set('email', 'no-es-un-correo')
                    ->set('password', 'contrasena-valida')->call('crear')),
            ],
            'nombre de categoría demasiado corto' => [
                'campo' => 'nombre',
                'legible' => 'nombre',
                ...$recoger(Livewire::test(ListaDeCategorias::class)->call('nuevo')
                    ->set('nombre', 'A')->call('crear')),
            ],
            'nombre de cliente demasiado corto' => [
                'campo' => 'nombre',
                'legible' => 'nombre',
                ...$recoger(Livewire::test(ListaDeClientes::class)->call('nuevo')
                    ->set('tipoDocumento', TipoDeDocumento::DNI->value)
                    ->set('numeroDocumento', '12345678')->set('nombre', 'A')->call('crear')),
            ],
        ];
    }

    /** Antes de afirmar nada: que los seis hayan rechazado de verdad. */
    public function test_los_seis_rechazos_ocurren_y_señalan_su_campo(): void
    {
        $this->comoAdministrador();

        foreach ($this->rechazosDeLaInterfaz() as $caso => $r) {
            $this->assertNotNull($r['campo'], "«{$caso}» no rechazó nada.");
            $this->assertNotSame('', $r['texto'], "«{$caso}» no dejó ningún mensaje en la pantalla.");
        }
    }

    /**
     * Detecta las mutaciones 1 y 2: con el idioma en inglés, o sin el archivo
     * de traducciones, el texto vuelve a las cadenas de Laravel.
     */
    public function test_ningun_mensaje_llega_en_ingles(): void
    {
        $this->comoAdministrador();

        foreach ($this->rechazosDeLaInterfaz() as $caso => $r) {
            $this->assertDoesNotMatchRegularExpression(
                self::MARCAS_EN_INGLES,
                $r['texto'],
                "«{$caso}» muestra un mensaje en inglés: {$r['texto']}"
            );
        }
    }

    /**
     * Detecta las mutaciones 3 y 4: el mapa vacío deja todos los mensajes con
     * el nombre técnico, y quitar una sola entrada deja solo ese.
     *
     * Se afirma campo por campo a propósito. Comprobar que "algún" mensaje
     * trae su nombre legible pasaría con veintiocho entradas presentes y una
     * faltante, que es justo el caso que más probable es que ocurra.
     */
    public function test_cada_mensaje_nombra_su_campo_como_lo_leeria_una_persona(): void
    {
        $this->comoAdministrador();

        foreach ($this->rechazosDeLaInterfaz() as $caso => $r) {
            $this->assertStringContainsString(
                $r['legible'],
                $r['texto'],
                "«{$caso}» no nombra el campo como «{$r['legible']}»: {$r['texto']}"
            );

            if ($r['legible'] !== $r['campo']) {
                $this->assertStringNotContainsString(
                    $r['campo'],
                    $r['texto'],
                    "«{$caso}» muestra el nombre técnico «{$r['campo']}» en vez de «{$r['legible']}»."
                );
            }
        }
    }

    /**
     * Los mensajes que escribe el dominio ya estaban en español y no los toca
     * este cambio. Se comprueba para que nadie los "traduzca" de nuevo y
     * termine con dos lugares donde vive el mismo texto.
     */
    public function test_los_mensajes_del_dominio_siguen_siendo_los_del_servicio(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Abarrotes']);

        $componente = Livewire::test(ListaDeCategorias::class)
            ->call('nuevo')->set('nombre', 'Abarrotes')->call('crear');

        $this->assertSame(
            'Ya existe una categoría con ese nombre.',
            $componente->get('errorDeCampo')
        );
    }
}
