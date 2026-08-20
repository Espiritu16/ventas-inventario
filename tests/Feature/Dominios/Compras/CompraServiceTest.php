<?php

namespace Tests\Feature\Dominios\Compras;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Compras\Modelos\Compra;
use App\Dominios\Compras\Servicios\CompraService;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Proveedores\Modelos\Proveedor;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CompraServiceTest extends TestCase
{
    use RefreshDatabase;

    private CompraService $servicio;

    private Proveedor $proveedor;

    private Producto $producto;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new CompraService(new InventarioService(new AuditoriaService));
        $this->proveedor = Proveedor::factory()->create();
        $this->producto = Producto::factory()->create();
        $this->usuario = Usuario::factory()->administrador()->create();
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = [], array $cambiosDeLinea = []): DatosDeEntrada
    {
        return DatosDeEntrada::desde(array_merge([
            'proveedor_id' => $this->proveedor->id,
            'tipo_documento' => '01',
            'serie_documento' => 'F001',
            'numero_documento' => '00001234',
            'fecha_emision' => now()->format('Y-m-d'),
            'lineas' => [array_merge([
                'producto_id' => $this->producto->id,
                'cantidad' => '10.000',
                'costo_unitario' => '5.0000',
                'codigo_lote' => 'L-001',
                'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
            ], $cambiosDeLinea)],
        ], $cambios));
    }

    // --- Registro (RF-006) ---

    public function test_una_compra_crea_el_lote_y_su_movimiento(): void
    {
        $compra = $this->servicio->registrar($this->datos(), (int) $this->usuario->id);

        $this->assertSame(0, bccomp($compra->total, '50.00', 2), 'El total sale de las líneas.');
        $this->assertSame(1, Lote::query()->count());
        $this->assertSame(1, MovimientoInventario::query()->where('tipo', 'ingreso')->count());
        $this->assertSame(1, $compra->lineas()->count());
        $this->assertNotNull($compra->lineas()->first()->lote_id, 'La línea guarda el lote que creó.');
    }

    /** El total lo calcula el sistema: un total enviado no se acepta. */
    public function test_el_total_enviado_se_ignora(): void
    {
        $compra = $this->servicio->registrar($this->datos(['total' => '999.99']), (int) $this->usuario->id);

        $this->assertSame(0, bccomp($compra->total, '50.00', 2));
    }

    public function test_una_compra_de_varias_lineas_suma_el_total(): void
    {
        $otro = Producto::factory()->create();

        $datos = $this->datos();
        $lineas = $datos->valor('lineas');
        $lineas[] = [
            'producto_id' => $otro->id,
            'cantidad' => '3.000',
            'costo_unitario' => '2.5000',
            'codigo_lote' => 'L-002',
            'fecha_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
        ];

        $compra = $this->servicio->registrar(
            DatosDeEntrada::desde(['lineas' => $lineas] + $datos->todos()),
            (int) $this->usuario->id
        );

        $this->assertSame(0, bccomp($compra->total, '57.50', 2));
        $this->assertSame(2, Lote::query()->count());
    }

    public function test_un_documento_repetido_del_mismo_proveedor_se_rechaza(): void
    {
        $this->servicio->registrar($this->datos(), (int) $this->usuario->id);

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos(), (int) $this->usuario->id),
            CodigoDeError::COMPRA_DOCUMENTO_DUPLICADO
        );

        $this->assertSame(1, Compra::query()->count(), 'Registrar dos veces la misma factura duplicaría stock.');
    }

    public function test_el_mismo_documento_de_otro_proveedor_si_se_acepta(): void
    {
        $this->servicio->registrar($this->datos(), (int) $this->usuario->id);

        $otro = Proveedor::factory()->create();
        $this->servicio->registrar($this->datos(['proveedor_id' => $otro->id]), (int) $this->usuario->id);

        $this->assertSame(2, Compra::query()->count());
    }

    public function test_una_compra_sin_lineas_se_rechaza(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos(['lineas' => []]), (int) $this->usuario->id),
            CodigoDeError::COMPRA_SIN_LINEAS
        );
    }

    public function test_una_compra_con_vencimiento_pasado_se_rechaza(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->registrar(
                $this->datos([], ['fecha_vencimiento' => now()->subDay()->format('Y-m-d')]),
                (int) $this->usuario->id
            ),
            CodigoDeError::LOTE_VENCIMIENTO_PASADO
        );
    }

    /**
     * Si una línea falla, no puede quedar ni la compra ni el stock: el
     * inventario dejaría de cuadrar con los documentos y nadie sabría cuál de
     * los dos tiene razón (RNF-003).
     */
    public function test_si_una_linea_falla_no_queda_ni_compra_ni_stock(): void
    {
        $datos = $this->datos();
        $lineas = $datos->valor('lineas');
        $lineas[] = [
            'producto_id' => $this->producto->id,
            'cantidad' => '1.000',
            'costo_unitario' => '3.0000',
            'codigo_lote' => 'L-002',
            'fecha_vencimiento' => now()->subDay()->format('Y-m-d'), // vencida
        ];

        try {
            $this->servicio->registrar(DatosDeEntrada::desde(['lineas' => $lineas] + $datos->todos()), (int) $this->usuario->id);
            $this->fail('Se aceptó una compra con una línea inválida.');
        } catch (ErrorDeDominio) {
            // esperado
        }

        $this->assertSame(0, Compra::query()->count());
        $this->assertSame(0, Lote::query()->count());
        $this->assertSame(0, MovimientoInventario::query()->count());
    }

    public static function fechasDeEmisionInvalidas(): array
    {
        return [
            'futura' => [1],
            'de hace más de un año' => [-400],
        ];
    }

    #[DataProvider('fechasDeEmisionInvalidas')]
    public function test_rechaza_una_fecha_de_emision_fuera_de_rango(int $dias): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->registrar(
                $this->datos(['fecha_emision' => now()->addDays($dias)->format('Y-m-d')]),
                (int) $this->usuario->id
            ),
            CodigoDeError::CAMPO_FUERA_DE_RANGO
        );
    }

    public function test_los_ceros_a_la_izquierda_del_numero_se_conservan(): void
    {
        $compra = $this->servicio->registrar($this->datos(['numero_documento' => '00001234']), (int) $this->usuario->id);

        $this->assertSame('00001234', $compra->numero_documento, 'El número del documento es una cadena impresa, no un entero.');
    }

    public function test_rechaza_un_proveedor_desactivado(): void
    {
        $this->proveedor->update(['activo' => false]);

        $this->assertRechaza(fn () => $this->servicio->registrar($this->datos(), (int) $this->usuario->id), CodigoDeError::RECURSO_NO_ENCONTRADO);
    }

    // --- Consulta ---

    public function test_el_listado_ordena_por_fecha_descendente(): void
    {
        $this->servicio->registrar($this->datos(['numero_documento' => '1', 'fecha_emision' => now()->subDays(3)->format('Y-m-d')]), (int) $this->usuario->id);
        $this->servicio->registrar($this->datos(['numero_documento' => '2', 'fecha_emision' => now()->format('Y-m-d')]), (int) $this->usuario->id);

        $this->assertSame('2', $this->servicio->listar()->items()[0]->numero_documento);
    }

    public function test_la_compra_se_consulta_con_sus_lineas_y_lotes(): void
    {
        $compra = $this->servicio->registrar($this->datos(), (int) $this->usuario->id);

        $encontrada = $this->servicio->encontrar((int) $compra->id);

        $this->assertCount(1, $encontrada->lineas);
        $this->assertNotNull($encontrada->lineas->first()->lote);
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
