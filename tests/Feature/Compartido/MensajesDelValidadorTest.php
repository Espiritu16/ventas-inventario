<?php

namespace Tests\Feature\Compartido;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Servicios\ProductoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El texto de un rechazo real, mirado en el mensaje que produce el servicio y
 * no en el archivo de traducciones.
 *
 * La diferencia importa: un archivo de traducciones puede existir y no estar
 * aplicándose —porque el idioma de la aplicación no lo usa, porque el entorno
 * lo pisa, porque la ruta no es la que Laravel mira— y desde el lado del código
 * las dos situaciones se ven idénticas. Solo se distinguen provocando el
 * rechazo y leyendo lo que sale.
 *
 * **Cubre el tramo del servicio, que es hasta donde llega este rol.** Lo que
 * la pantalla hace después con ese mensaje se verifica en
 * `tests/Feature/Livewire/`, que es de `implementation-frontend`. Las dos
 * mitades hacen falta: esta prueba no vería que una pantalla ignore el mensaje
 * y ponga su propio texto.
 */
final class MensajesDelValidadorTest extends TestCase
{
    use RefreshDatabase;

    /** Palabras que solo aparecen si el mensaje quedó sin traducir. */
    private const DELATORAS = ['field', 'must', 'invalid', 'characters', 'The '];

    private ProductoService $servicio;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ProductoService(new AuditoriaService);
        $this->categoria = Categoria::factory()->create(['nombre' => 'Abarrotes']);
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function rechazosGenericos(): array
    {
        return [
            'código con espacio' => [['codigo' => 'ARR 001'], 'código'],
            'código vacío' => [['codigo' => ''], 'código'],
            'código demasiado largo' => [['codigo' => 'A'.str_repeat('B', 40)], 'código'],
            'nombre demasiado corto' => [['nombre' => 'ab'], 'nombre'],
            'unidad de medida vacía' => [['unidad_medida' => ''], 'unidad de medida'],
            'precio mal escrito' => [['precio_menor' => 'diez'], 'precio al por menor'],
            'stock mínimo mal escrito' => [['stock_minimo' => 'poco'], 'stock mínimo'],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[DataProvider('rechazosGenericos')]
    public function test_el_rechazo_llega_en_espanol_y_nombra_el_campo_como_lo_llama_la_gente(
        array $cambios,
        string $nombreEsperado,
    ): void {
        $mensaje = $this->mensajeAlCrear($cambios);

        foreach (self::DELATORAS as $delatora) {
            $this->assertStringNotContainsString($delatora, $mensaje, "El mensaje quedó sin traducir: «{$mensaje}»");
        }

        $this->assertStringContainsString(
            $nombreEsperado,
            $mensaje,
            "El mensaje no nombra el campo como lo llamaría quien opera: «{$mensaje}»"
        );
    }

    /**
     * Traducir sin el mapa de nombres deja «El campo codigo no tiene un
     * formato válido»: español, y todavía jerga técnica, que el contrato de
     * errores prohíbe. Esta es la mitad que traducir no resuelve.
     */
    public function test_el_mensaje_no_nombra_el_campo_por_su_identificador(): void
    {
        $mensaje = $this->mensajeAlCrear(['codigo' => 'ARR 001']);

        $this->assertStringNotContainsString(' codigo', $mensaje, "Sigue nombrando el identificador: «{$mensaje}»");
        $this->assertStringContainsString('código', $mensaje);
    }

    /**
     * Los mensajes que escribe el servicio ya estaban en español y no los toca
     * este cambio. La superficie era el tramo genérico, y una traducción que
     * además pisara los de dominio estaría haciendo de más.
     */
    public function test_los_mensajes_del_dominio_siguen_como_estaban(): void
    {
        $this->servicio->crear($this->datos());

        $this->assertSame('Ya existe un producto con ese código.', $this->mensajeAlCrear([]));
    }

    /** @param  array<string, mixed>  $cambios */
    private function mensajeAlCrear(array $cambios): string
    {
        try {
            $this->servicio->crear($this->datos($cambios));
        } catch (ErrorDeDominio $error) {
            return $error->getMessage();
        }

        $this->fail('El servicio no rechazó nada, así que no hay texto que mirar.');
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = []): DatosDeCatalogo
    {
        return DatosDeCatalogo::desde(array_merge([
            'codigo' => 'ARROZ-01',
            'nombre' => 'Arroz extra',
            'categoria_id' => $this->categoria->id,
            'unidad_medida' => 'KGM',
            'precio_menor' => '10.0000',
            'precio_mayor' => '8.0000',
        ], $cambios));
    }
}
