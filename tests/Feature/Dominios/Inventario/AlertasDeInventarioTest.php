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
use Tests\TestCase;

/**
 * Alertas del tablero: vencimiento (RF-018) y stock bajo (RF-019).
 *
 * Las dos alertas comparten una definición y por eso comparten archivo: qué
 * cuenta como disponible. Un lote vencido tiene mercadería física y no tiene
 * nada vendible, así que la misma unidad aparece en una alerta y falta en la
 * otra. Probarlas por separado dejaría esa coherencia sin dueño.
 */
final class AlertasDeInventarioTest extends TestCase
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
        $this->producto = Producto::factory()->create(['nombre' => 'Leche entera', 'stock_minimo' => '0.000']);
    }

    /** Fecha civil a `n` días de hoy, con el mismo «hoy» que usa el servicio. */
    private function enDias(int $dias): string
    {
        return now()->addDays($dias)->format('Y-m-d');
    }

    private function lote(string $vence, string $cantidad = '10.000', ?Producto $producto = null): Lote
    {
        return Lote::factory()->venceEl($vence)->create([
            'producto_id' => ($producto ?? $this->producto)->id,
            'cantidad_actual' => $cantidad,
            'codigo_lote' => 'L-'.substr(str_replace('-', '', $vence), 2),
        ]);
    }

    // --- RF-018: lotes por vencer y vencidos ---

    public function test_un_lote_que_entra_en_el_plazo_aparece_sin_intervencion(): void
    {
        $cerca = $this->lote($this->enDias(10));
        $lejos = $this->lote($this->enDias(100));

        $ids = $this->consulta->lotesPorVencer(30)->pluck('id')->all();

        $this->assertContains($cerca->id, $ids);
        $this->assertNotContains($lejos->id, $ids, 'Fuera del plazo no es una alerta.');
    }

    public function test_cambiar_el_plazo_recalcula_la_lista(): void
    {
        $lote = $this->lote($this->enDias(45));

        $this->assertNotContains($lote->id, $this->consulta->lotesPorVencer(7)->pluck('id')->all());
        $this->assertContains($lote->id, $this->consulta->lotesPorVencer(60)->pluck('id')->all());
    }

    /** El plazo por defecto es el que fija el contrato de `GET /panel`: 30 días. */
    public function test_sin_plazo_explicito_alcanza_hasta_treinta_dias(): void
    {
        $justo = $this->lote($this->enDias(30));
        $pasado = $this->lote($this->enDias(31));

        $ids = $this->consulta->lotesPorVencer()->pluck('id')->all();

        $this->assertContains($justo->id, $ids, 'El día 30 entra: el plazo es inclusivo.');
        $this->assertNotContains($pasado->id, $ids);
    }

    /**
     * El vencido con existencia es el caso que la alerta existe para mostrar, y
     * el mismo que el disponible tiene que dejar fuera. Las dos mitades se
     * comprueban juntas a propósito: si una alerta dijera «vencido» sobre algo
     * que el stock sigue considerando vendible, la contradicción no aparecería
     * probando cada lado por su cuenta.
     */
    public function test_el_vencido_con_existencia_aparece_marcado_y_no_suma_al_disponible(): void
    {
        $vencido = $this->lote($this->enDias(-3), '99.000');
        $this->lote($this->enDias(10), '4.000');

        $fila = $this->consulta->lotesPorVencer(30)->firstWhere('id', $vencido->id);

        $this->assertNotNull($fila, 'Un vencido con existencia siempre es una alerta, sea cual sea el plazo.');
        $this->assertTrue($fila['vencido']);
        $this->assertSame(-3, $fila['dias_para_vencer']);
        $this->assertSame(
            0,
            bccomp($this->inventario->stockDisponible((int) $this->producto->id), '4.000', 3),
            'Las 99 unidades vencidas no son stock disponible.'
        );
    }

    public function test_un_lote_sin_existencia_no_aparece_aunque_este_vencido(): void
    {
        $agotado = $this->lote($this->enDias(-10), '0.000');

        $this->assertNotContains($agotado->id, $this->consulta->lotesPorVencer(30)->pluck('id')->all());
    }

    /** Urgencia: primero lo que venció hace más tiempo, después lo que vence antes. */
    public function test_ordena_por_urgencia(): void
    {
        $proximo = $this->lote($this->enDias(20));
        $inminente = $this->lote($this->enDias(2));
        $vencido = $this->lote($this->enDias(-5));

        $this->assertSame(
            [$vencido->id, $inminente->id, $proximo->id],
            $this->consulta->lotesPorVencer(30)->pluck('id')->all()
        );
    }

    public function test_la_alerta_de_vencimiento_no_lleva_costo(): void
    {
        $this->lote($this->enDias(5));

        $serializado = (string) json_encode($this->consulta->lotesPorVencer(30)->all());

        $this->assertStringNotContainsString('costo', $serializado, 'El tablero lo ve también el vendedor.');
        $this->assertStringContainsString('Leche entera', $serializado, 'Pero sí dice de qué producto es el lote.');
    }

    public function test_el_plazo_fuera_de_rango_se_rechaza(): void
    {
        foreach ([0, -1, 366] as $dias) {
            try {
                $this->consulta->lotesPorVencer($dias);
                $this->fail("El plazo {$dias} tendría que rechazarse.");
            } catch (ErrorDeDominio $error) {
                $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
                $this->assertSame('dias', $error->detalle['campo'] ?? null);
            }
        }
    }

    // --- RF-019: productos bajo el mínimo ---

    public function test_al_caer_al_minimo_aparece_y_al_reponerse_desaparece(): void
    {
        $this->producto->update(['stock_minimo' => '5.000']);
        $this->ingresar('5.000');

        $fila = $this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id);

        $this->assertNotNull($fila, 'Estar justo en el mínimo ya es la alerta: el criterio es «menor o igual».');
        $this->assertSame(0, bccomp($fila['stock_disponible'], '5.000', 3));
        $this->assertSame(0, bccomp($fila['stock_minimo'], '5.000', 3));

        $this->ingresar('1.000', 'L-REPOSICION');

        $this->assertNull(
            $this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id),
            'Repuesto por una compra, deja de ser alerta.'
        );
    }

    public function test_por_encima_del_minimo_no_aparece(): void
    {
        $this->producto->update(['stock_minimo' => '5.000']);
        $this->ingresar('6.000');

        $this->assertNull($this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id));
    }

    public function test_un_producto_sin_ningun_lote_aparece_con_disponible_cero(): void
    {
        $this->producto->update(['stock_minimo' => '3.000']);

        $fila = $this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id);

        $this->assertNotNull($fila, 'Nunca haber tenido stock es la forma más aguda de estar bajo el mínimo.');
        $this->assertSame(0, bccomp($fila['stock_disponible'], '0.000', 3));
    }

    /**
     * La mercadería vencida está en el depósito y no se puede vender, así que
     * no cuenta para el mínimo. Es el caso que separa esta consulta de una que
     * sume `lotes.cantidad_actual` a secas: con cien unidades en el estante, la
     * versión ingenua declara el producto abastecido y nadie repone.
     */
    public function test_el_stock_vencido_no_cuenta_para_el_minimo(): void
    {
        $this->producto->update(['stock_minimo' => '5.000']);
        $this->lote($this->enDias(-1), '100.000');

        $fila = $this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id);

        $this->assertNotNull($fila, 'Cien unidades vencidas no abastecen nada.');
        $this->assertSame(0, bccomp($fila['stock_disponible'], '0.000', 3));
    }

    public function test_los_productos_inactivos_no_se_listan(): void
    {
        $inactivo = Producto::factory()->inactivo()->create(['stock_minimo' => '10.000']);

        $this->assertNull($this->consulta->productosBajoMinimo()->firstWhere('id', $inactivo->id));
    }

    public function test_la_alerta_de_stock_bajo_no_lleva_costo(): void
    {
        $this->producto->update(['stock_minimo' => '5.000']);
        $this->ingresar('1.000', 'L-CARO', '999.9999');

        $serializado = (string) json_encode($this->consulta->productosBajoMinimo()->all());

        $this->assertStringNotContainsString('costo', $serializado);
        $this->assertStringNotContainsString('999.9999', $serializado);
    }

    private function ingresar(string $cantidad, string $codigoLote = 'L-001', string $costo = '5.0000'): void
    {
        $this->inventario->ingresar(
            (int) $this->producto->id,
            $cantidad,
            $costo,
            $codigoLote,
            $this->enDias(180),
            MovimientoInventario::ORIGEN_COMPRA,
            1,
            (int) Usuario::factory()->create()->id,
        );
    }
}
