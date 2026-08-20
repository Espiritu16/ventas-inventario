<?php

namespace Tests\Feature\Dominios\Inventario;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class InventarioServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventarioService $servicio;

    private Producto $producto;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new InventarioService(new AuditoriaService);
        $this->producto = Producto::factory()->create();
        $this->usuario = Usuario::factory()->administrador()->create();
    }

    private function ingresar(array $cambios = []): Lote
    {
        $datos = array_merge([
            'productoId' => $this->producto->id,
            'cantidad' => '10.000',
            'costoUnitario' => '5.0000',
            'codigoLote' => 'L-001',
            'fechaVencimiento' => now()->addMonths(6)->format('Y-m-d'),
        ], $cambios);

        return $this->servicio->ingresar(
            productoId: (int) $datos['productoId'],
            cantidad: $datos['cantidad'],
            costoUnitario: $datos['costoUnitario'],
            codigoLote: $datos['codigoLote'],
            fechaVencimiento: $datos['fechaVencimiento'],
            origenTipo: MovimientoInventario::ORIGEN_COMPRA,
            origenId: 1,
            usuarioId: (int) $this->usuario->id,
        );
    }

    // --- Ingreso (RF-006) ---

    public function test_un_ingreso_crea_el_lote_y_su_movimiento(): void
    {
        $lote = $this->ingresar();

        $this->assertSame(0, bccomp($lote->cantidad_actual, '10.000', 3));
        $this->assertDatabaseCount('movimientos_inventario', 1);

        $movimiento = MovimientoInventario::query()->first();
        $this->assertSame('ingreso', $movimiento->tipo);
        $this->assertSame(0, bccomp($movimiento->cantidad, '10.000', 3));
        $this->assertSame($this->usuario->id, $movimiento->usuario_id);
    }

    /** Mismo producto, lote, vencimiento y costo: se acumula en la misma fila. */
    public function test_dos_ingresos_identicos_se_acumulan_en_un_lote(): void
    {
        $this->ingresar();
        $lote = $this->ingresar(['cantidad' => '5.500']);

        $this->assertSame(1, Lote::query()->count());
        $this->assertSame(0, bccomp($lote->cantidad_actual, '15.500', 3));
        $this->assertDatabaseCount('movimientos_inventario', 2);
    }

    /**
     * Un costo distinto abre lote nuevo: es lo que permite calcular después la
     * utilidad con el costo real de cada porción vendida, y no con un promedio.
     */
    public function test_un_costo_distinto_abre_un_lote_nuevo(): void
    {
        $this->ingresar(['costoUnitario' => '5.0000']);
        $this->ingresar(['costoUnitario' => '6.0000']);

        $this->assertSame(2, Lote::query()->count());
    }

    public function test_un_vencimiento_distinto_abre_un_lote_nuevo(): void
    {
        $this->ingresar(['fechaVencimiento' => now()->addMonths(6)->format('Y-m-d')]);
        $this->ingresar(['fechaVencimiento' => now()->addMonths(8)->format('Y-m-d')]);

        $this->assertSame(2, Lote::query()->count());
    }

    public static function vencimientosRechazados(): array
    {
        return [
            'ayer' => [-1],
            'hoy' => [0],
        ];
    }

    /** Ingresar mercadería vencida —o que vence hoy— se rechaza (RF-006). */
    #[DataProvider('vencimientosRechazados')]
    public function test_rechaza_un_vencimiento_que_no_es_futuro(int $dias): void
    {
        $this->assertRechaza(
            fn () => $this->ingresar(['fechaVencimiento' => now()->addDays($dias)->format('Y-m-d')]),
            CodigoDeError::LOTE_VENCIMIENTO_PASADO
        );

        $this->assertSame(0, Lote::query()->count());
    }

    public function test_rechaza_un_producto_desactivado(): void
    {
        $this->producto->update(['activo' => false]);

        $this->assertRechaza(fn () => $this->ingresar(), CodigoDeError::PRODUCTO_INACTIVO);
    }

    public function test_rechaza_un_producto_inexistente(): void
    {
        $this->assertRechaza(fn () => $this->ingresar(['productoId' => 9999]), CodigoDeError::RECURSO_NO_ENCONTRADO);
    }

    // --- Ajuste (RF-009) ---

    public function test_un_ajuste_registra_la_diferencia_y_no_la_cantidad_nueva(): void
    {
        $lote = $this->ingresar();

        $ajustado = $this->servicio->ajustar((int) $lote->id, '7.000', 'merma', null, (int) $this->usuario->id);

        $this->assertSame(0, bccomp($ajustado->cantidad_actual, '7.000', 3));

        $movimiento = MovimientoInventario::query()->where('tipo', 'ajuste')->first();
        $this->assertSame(0, bccomp($movimiento->cantidad, '-3.000', 3), 'El kardex registra el cambio, no el saldo.');
        $this->assertSame('merma', $movimiento->motivo);
    }

    public function test_el_ajuste_deja_rastro_en_auditoria_con_su_responsable(): void
    {
        $lote = $this->ingresar();

        $this->servicio->ajustar((int) $lote->id, '4.000', 'rotura', null, (int) $this->usuario->id);

        $fila = DB::table('auditorias')->where('entidad', 'Lote')->first();
        $this->assertNotNull($fila);
        $this->assertSame($this->usuario->id, (int) $fila->usuario_id);
        $this->assertSame(0, bccomp(json_decode($fila->valores_anteriores, true)['cantidad_actual'], '10.000', 3));
    }

    public static function motivosInvalidos(): array
    {
        return [
            'vacío' => [''],
            'inventado' => ['porque_si'],
            'con mayúsculas' => ['MERMA'],
        ];
    }

    #[DataProvider('motivosInvalidos')]
    public function test_un_ajuste_sin_motivo_valido_se_rechaza(string $motivo): void
    {
        $lote = $this->ingresar();

        $this->assertRechaza(
            fn () => $this->servicio->ajustar((int) $lote->id, '5.000', $motivo, null, (int) $this->usuario->id),
            CodigoDeError::AJUSTE_SIN_MOTIVO
        );

        $this->assertSame(0, bccomp($lote->refresh()->cantidad_actual, '10.000', 3));
    }

    /** Un descuadre por conteo necesita explicación, o la auditoría no dice nada. */
    public function test_el_error_de_conteo_exige_observacion(): void
    {
        $lote = $this->ingresar();

        $this->assertRechaza(
            fn () => $this->servicio->ajustar((int) $lote->id, '5.000', 'error_conteo', null, (int) $this->usuario->id),
            CodigoDeError::CAMPO_REQUERIDO
        );

        $this->servicio->ajustar((int) $lote->id, '5.000', 'error_conteo', 'Faltaban tres cajas en el conteo', (int) $this->usuario->id);
        $this->assertSame(0, bccomp($lote->refresh()->cantidad_actual, '5.000', 3));
    }

    public function test_un_ajuste_a_cantidad_negativa_se_rechaza(): void
    {
        $lote = $this->ingresar();

        $this->assertRechaza(
            fn () => $this->servicio->ajustar((int) $lote->id, '-1.000', 'merma', null, (int) $this->usuario->id),
            CodigoDeError::AJUSTE_CANTIDAD_NEGATIVA
        );
    }

    public function test_ajustar_a_la_misma_cantidad_no_ensucia_el_kardex(): void
    {
        $lote = $this->ingresar();

        $this->servicio->ajustar((int) $lote->id, '10.000', 'merma', null, (int) $this->usuario->id);

        $this->assertSame(0, MovimientoInventario::query()->where('tipo', 'ajuste')->count());
    }

    public function test_ajustar_un_lote_inexistente_no_lo_encuentra(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->ajustar(9999, '1.000', 'merma', null, (int) $this->usuario->id),
            CodigoDeError::RECURSO_NO_ENCONTRADO
        );
    }

    // --- Coherencia entre saldo e historial (ADR-0004) ---

    /**
     * El saldo del lote y la suma de su kardex tienen que coincidir siempre.
     * Si divergen, el sistema no sabe cuánto hay: uno de los dos miente y no
     * hay forma de saber cuál.
     */
    public function test_la_suma_del_kardex_coincide_con_el_saldo_del_lote(): void
    {
        $lote = $this->ingresar();
        $this->ingresar(['cantidad' => '2.500']);
        $this->servicio->ajustar((int) $lote->id, '9.000', 'merma', null, (int) $this->usuario->id);
        $this->servicio->ajustar((int) $lote->id, '11.250', 'error_conteo', 'Aparecieron dos cajas', (int) $this->usuario->id);

        $sumaDelKardex = MovimientoInventario::query()->where('lote_id', $lote->id)->sum('cantidad');

        $this->assertSame(
            0,
            bccomp($lote->refresh()->cantidad_actual, number_format((float) $sumaDelKardex, 3, '.', ''), 3),
            'El saldo del lote y la suma de sus movimientos divergen.'
        );
    }

    // --- Stock disponible (RF-007) ---

    public function test_el_stock_disponible_excluye_los_lotes_vencidos(): void
    {
        Lote::factory()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '4.000']);
        Lote::factory()->vencido()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '99.000', 'costo_unitario' => '9.0000']);

        $this->assertSame(0, bccomp($this->servicio->stockDisponible((int) $this->producto->id), '4.000', 3));
    }

    public function test_los_lotes_se_ordenan_por_vencimiento_mas_proximo(): void
    {
        Lote::factory()->venceEl('2027-01-31')->create(['producto_id' => $this->producto->id, 'codigo_lote' => 'TARDE']);
        Lote::factory()->venceEl('2026-12-01')->create(['producto_id' => $this->producto->id, 'codigo_lote' => 'PRIMERO']);
        Lote::factory()->venceEl('2027-01-01')->create(['producto_id' => $this->producto->id, 'codigo_lote' => 'MEDIO']);

        $orden = $this->servicio->lotesDe((int) $this->producto->id)->pluck('codigo_lote')->all();

        $this->assertSame(['PRIMERO', 'MEDIO', 'TARDE'], $orden, 'El orden decide qué se vende primero (FEFO).');
    }

    private function assertRechaza(callable $operacion, CodigoDeError $esperado): void
    {
        try {
            $operacion();
            $this->fail("Se aceptó algo que el contrato rechaza con {$esperado->value}.");
        } catch (ErrorDeDominio $error) {
            $this->assertSame($esperado, $error->codigo, "Se esperaba {$esperado->value} y llegó {$error->codigo->value}.");
        }
    }
}
