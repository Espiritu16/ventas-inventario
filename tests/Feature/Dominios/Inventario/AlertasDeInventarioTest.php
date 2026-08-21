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

    /** Costo y cantidad del lote testigo: cualquier valor derivado de ellos se reconoce. */
    private const COSTO_TESTIGO = '777.7777';

    private const CANTIDAD_TESTIGO = '13.000';

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

    /**
     * Deny-by-default sobre la forma de la alerta, igual que RNF-013 resuelve
     * las rutas: se afirma el conjunto de campos que la alerta **sí** puede
     * llevar, en vez de prohibir la subcadena «costo».
     *
     * Una lista negra solo detecta lo que alguien anticipó nombrar. Un campo
     * `valor_en_riesgo` = cantidad x costo unitario pasaba entero por el
     * guardián anterior, y el costo se recupera dividiéndolo por la cantidad,
     * que viaja en la misma fila. Sería la quinta superficie sobre el mismo
     * dato, y en la única pantalla que vendedor y administrador comparten.
     *
     * Un campo nuevo tiene que costar una decisión explícita acá, no colarse.
     */
    public function test_la_alerta_de_vencimiento_solo_lleva_los_campos_declarados(): void
    {
        $lote = $this->lote($this->enDias(5), self::CANTIDAD_TESTIGO);
        Lote::query()->whereKey($lote->id)->update(['costo_unitario' => self::COSTO_TESTIGO]);

        $fila = $this->consulta->lotesPorVencer(30)->firstWhere('id', $lote->id);

        $this->assertSame([
            'id',
            'producto_id',
            'producto_codigo',
            'producto_nombre',
            'codigo_lote',
            'fecha_vencimiento',
            'cantidad_actual',
            'vencido',
            'dias_para_vencer',
        ], array_keys($fila), 'Campo nuevo en la alerta de vencimiento: hay que decidir si el vendedor puede verlo.');

        $this->assertSame('Leche entera', $fila['producto_nombre'], 'Pero sí dice de qué producto es el lote.');
        $this->assertNingunValorReconstruyeElCosto($fila);
    }

    public function test_el_plazo_fuera_de_rango_se_rechaza(): void
    {
        foreach ([0, -1, ConsultaDeInventarioService::DIAS_POR_VENCER_MAXIMO + 1] as $dias) {
            try {
                $this->consulta->lotesPorVencer($dias);
                $this->fail("El plazo {$dias} tendría que rechazarse.");
            } catch (ErrorDeDominio $error) {
                $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
                $this->assertSame('dias', $error->detalle['campo'] ?? null);
            }
        }

        // Los extremos del intervalo sí entran: el rechazo es afuera, no en el
        // borde. Sin esto, el plazo máximo se podría mover sin que nada falle.
        foreach ([
            ConsultaDeInventarioService::DIAS_POR_VENCER_MINIMO,
            ConsultaDeInventarioService::DIAS_POR_VENCER_MAXIMO,
        ] as $dias) {
            $this->assertNotNull($this->consulta->lotesPorVencer($dias), "El plazo {$dias} es válido.");
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

    /** El mismo guardián, por el mismo motivo: las dos alertas son la misma pantalla. */
    public function test_la_alerta_de_stock_bajo_solo_lleva_los_campos_declarados(): void
    {
        $this->producto->update(['stock_minimo' => '20.000']);
        $this->ingresar(self::CANTIDAD_TESTIGO, 'L-CARO', self::COSTO_TESTIGO);

        $fila = $this->consulta->productosBajoMinimo()->firstWhere('id', $this->producto->id);

        $this->assertSame([
            'id',
            'codigo',
            'nombre',
            'unidad_medida',
            'stock_disponible',
            'stock_minimo',
        ], array_keys($fila), 'Campo nuevo en la alerta de stock bajo: hay que decidir si el vendedor puede verlo.');

        $this->assertNingunValorReconstruyeElCosto($fila);
    }

    /**
     * La segunda mitad del guardián: que la lista de campos sea la declarada no
     * alcanza si uno de esos campos trae el costo adentro.
     *
     * Se comprueba sobre valores, no sobre nombres. La cantidad viaja en la
     * misma fila, así que un importe —cantidad x costo— es costo servido:
     * dividir es todo el trabajo que hay que hacer para recuperarlo.
     *
     * @param  array<string, mixed>  $fila
     */
    private function assertNingunValorReconstruyeElCosto(array $fila): void
    {
        $derivado = bcmul(self::COSTO_TESTIGO, self::CANTIDAD_TESTIGO, 4);

        foreach ($fila as $campo => $valor) {
            if (! is_numeric($valor)) {
                continue;
            }

            $this->assertNotSame(
                0,
                bccomp((string) $valor, self::COSTO_TESTIGO, 4),
                "«{$campo}» trae el costo unitario del lote."
            );

            $this->assertNotSame(
                0,
                bccomp((string) $valor, $derivado, 4),
                "«{$campo}» dividido por la cantidad de la misma fila devuelve el costo unitario."
            );
        }
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
