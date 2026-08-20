<?php

namespace Tests\Feature\Dominios\Inventario;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\ConsultaDeInventarioService;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class ConsultaDeInventarioTest extends TestCase
{
    use RefreshDatabase;

    private ConsultaDeInventarioService $consulta;

    private InventarioService $inventario;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventario = new InventarioService(new AuditoriaService);
        $this->consulta = new ConsultaDeInventarioService($this->inventario);
        $this->producto = Producto::factory()->create();
    }

    // --- Proyección por rol (matriz de permisos) ---

    /**
     * Hasta este sprint "sin costo ni margen" no tenía efecto: el costo nace
     * con el lote. Ahora existe, y una prueba que lo afirmara antes pasaba por
     * vacía.
     */
    public function test_el_administrador_ve_el_costo_del_lote(): void
    {
        Lote::factory()->create(['producto_id' => $this->producto->id, 'costo_unitario' => '7.5000']);

        $lote = $this->stockPara(Usuario::factory()->administrador()->create())[0]['lotes'][0];

        $this->assertArrayHasKey('costo_unitario', $lote);
        $this->assertSame(0, bccomp($lote['costo_unitario'], '7.5000', 4));
    }

    public function test_el_vendedor_no_recibe_el_costo_del_lote(): void
    {
        Lote::factory()->create(['producto_id' => $this->producto->id, 'costo_unitario' => '7.5000']);

        $lote = $this->stockPara(Usuario::factory()->create())[0]['lotes'][0];

        $this->assertArrayNotHasKey('costo_unitario', $lote, 'El costo es información de negociación con el proveedor.');
        $this->assertArrayHasKey('cantidad_actual', $lote, 'Pero sí necesita saber cuánto hay para vender.');
    }

    public function test_el_costo_no_viaja_en_ninguna_parte_de_la_respuesta_del_vendedor(): void
    {
        Lote::factory()->create(['producto_id' => $this->producto->id, 'costo_unitario' => '7.5000']);

        $serializado = json_encode($this->stockPara(Usuario::factory()->create()));

        $this->assertStringNotContainsString('7.5000', $serializado);
        $this->assertStringNotContainsString('costo', $serializado);
    }

    // --- Stock y orden (RF-007) ---

    public function test_el_disponible_excluye_vencidos_y_los_marca(): void
    {
        Lote::factory()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '4.000']);
        Lote::factory()->vencido()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '99.000', 'costo_unitario' => '9.0000']);

        $fila = $this->stockPara(Usuario::factory()->administrador()->create())[0];

        $this->assertSame(0, bccomp($fila['stock_disponible'], '4.000', 3));
        $this->assertCount(2, $fila['lotes'], 'Los vencidos se listan, pero no suman.');
        $this->assertTrue(collect($fila['lotes'])->firstWhere('vencido', true) !== null);
    }

    public function test_los_lotes_vienen_ordenados_por_vencimiento(): void
    {
        Lote::factory()->venceEl('2027-03-01')->create(['producto_id' => $this->producto->id, 'codigo_lote' => 'TARDE']);
        Lote::factory()->venceEl('2026-12-01')->create(['producto_id' => $this->producto->id, 'codigo_lote' => 'PRIMERO']);

        $lotes = $this->stockPara(Usuario::factory()->administrador()->create())[0]['lotes'];

        $this->assertSame('PRIMERO', $lotes[0]['codigo_lote']);
    }

    // --- Kardex (RF-008) ---

    public function test_el_kardex_lista_los_movimientos_del_producto(): void
    {
        $usuario = Usuario::factory()->administrador()->create();
        $lote = $this->inventario->ingresar(
            (int) $this->producto->id, '10.000', '5.0000', 'L-001',
            now()->addMonths(6)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA, 1, (int) $usuario->id,
        );
        $this->inventario->ajustar((int) $lote->id, '8.000', 'merma', null, (int) $usuario->id);

        $pagina = $this->consulta->kardex((int) $this->producto->id);

        $this->assertSame(2, $pagina->total());
        $this->assertSame('ajuste', $pagina->items()[0]->tipo, 'El más reciente primero.');
        $this->assertNotNull($pagina->items()[0]->usuario, 'Cada movimiento dice quién lo hizo.');
    }

    public function test_el_kardex_de_un_producto_inexistente_no_lo_encuentra(): void
    {
        try {
            $this->consulta->kardex(9999);
            $this->fail('Se consultó el kardex de un producto que no existe.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::RECURSO_NO_ENCONTRADO, $error->codigo);
        }
    }

    public function test_un_rango_invertido_se_rechaza(): void
    {
        try {
            $this->consulta->kardex((int) $this->producto->id, '2026-08-20', '2026-08-01');
            $this->fail('Se aceptó un rango invertido.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
        }
    }

    /**
     * El rango son fechas civiles que alguien elige mirando un calendario de
     * Lima; `created_at` es un instante en UTC. Se convierte el rango, no el
     * dato: sin eso, lo ocurrido en las últimas cinco horas del día caería
     * fuera del día que la persona pidió.
     */
    public function test_un_movimiento_de_la_noche_de_lima_cae_en_su_propio_dia(): void
    {
        $usuario = Usuario::factory()->administrador()->create();
        $lote = Lote::factory()->create(['producto_id' => $this->producto->id]);

        // 22:00 en Lima del día 15 son las 03:00 UTC del día 16.
        $instante = Carbon::parse('2026-08-15 22:00:00', config('app.timezone_visualizacion'))->utc();

        MovimientoInventario::query()->create([
            'lote_id' => $lote->id, 'producto_id' => $this->producto->id,
            'tipo' => 'ingreso', 'cantidad' => '1.000', 'costo_unitario' => '5.0000',
            'origen_tipo' => 'compra', 'origen_id' => 1,
            'usuario_id' => $usuario->id, 'created_at' => $instante,
        ]);

        $this->assertSame(1, $this->consulta->kardex((int) $this->producto->id, '2026-08-15', '2026-08-15')->total());
        $this->assertSame(0, $this->consulta->kardex((int) $this->producto->id, '2026-08-16', '2026-08-16')->total());
    }

    /** @return array<int, array<string, mixed>> */
    private function stockPara(Usuario $actor): array
    {
        return $this->consulta->stock($actor)->items();
    }
}
