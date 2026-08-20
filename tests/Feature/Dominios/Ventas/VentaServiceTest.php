<?php

namespace Tests\Feature\Dominios\Ventas;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Modelos\Comprobante;
use App\Dominios\Comprobantes\Modelos\SerieComprobante;
use App\Dominios\Comprobantes\Servicios\SerieComprobanteService;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Ventas\Modelos\DetalleVentaLote;
use App\Dominios\Ventas\Modelos\Venta;
use App\Dominios\Ventas\Servicios\VentaService;
use Database\Seeders\SeriesComprobanteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class VentaServiceTest extends TestCase
{
    use RefreshDatabase;

    private VentaService $servicio;

    private InventarioService $inventario;

    private Producto $producto;

    private Cliente $cliente;

    private Usuario $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventario = new InventarioService(new AuditoriaService);
        $this->servicio = new VentaService($this->inventario, new SerieComprobanteService);

        $this->producto = Producto::factory()->create(['precio_menor' => '10.0000', 'precio_mayor' => '8.0000']);
        $this->cliente = Cliente::factory()->create(['direccion' => 'Av. Siempre Viva 123']);
        $this->vendedor = Usuario::factory()->create();

        $this->seed(SeriesComprobanteSeeder::class);
    }

    private function ingresar(string $cantidad, string $costo = '5.0000', ?string $vence = null, string $codigo = 'L-001'): Lote
    {
        return $this->inventario->ingresar(
            (int) $this->producto->id, $cantidad, $costo, $codigo,
            $vence ?? now()->addMonths(6)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA, 1, (int) $this->vendedor->id,
        );
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = [], array $linea = []): DatosDeEntrada
    {
        return DatosDeEntrada::desde(array_merge([
            'cliente_id' => $this->cliente->id,
            'tipo_comprobante' => SerieComprobante::TIPO_BOLETA,
            'metodo_pago' => 'efectivo',
            'lineas' => [array_merge([
                'producto_id' => $this->producto->id,
                'cantidad' => '2.000',
                'tipo_precio' => 'menor',
            ], $linea)],
        ], $cambios));
    }

    // --- FEFO (RF-012) ---

    public function test_descuenta_del_lote_que_vence_primero(): void
    {
        $tarde = $this->ingresar('10.000', '5.0000', now()->addMonths(9)->format('Y-m-d'), 'TARDE');
        $primero = $this->ingresar('10.000', '5.0000', now()->addMonths(2)->format('Y-m-d'), 'PRIMERO');

        $this->servicio->registrar($this->datos(), $this->vendedor);

        $this->assertSame(0, bccomp($primero->refresh()->cantidad_actual, '8.000', 3), 'Sale del que vence antes.');
        $this->assertSame(0, bccomp($tarde->refresh()->cantidad_actual, '10.000', 3), 'El otro no se toca.');
    }

    public function test_reparte_entre_varios_lotes_cuando_uno_no_alcanza(): void
    {
        $primero = $this->ingresar('3.000', '5.0000', now()->addMonths(2)->format('Y-m-d'), 'PRIMERO');
        $segundo = $this->ingresar('10.000', '6.0000', now()->addMonths(5)->format('Y-m-d'), 'SEGUNDO');

        $venta = $this->servicio->registrar($this->datos([], ['cantidad' => '5.000']), $this->vendedor);

        $this->assertSame(0, bccomp($primero->refresh()->cantidad_actual, '0.000', 3));
        $this->assertSame(0, bccomp($segundo->refresh()->cantidad_actual, '8.000', 3));

        $reparto = DetalleVentaLote::query()->orderBy('id')->get();
        $this->assertCount(2, $reparto, 'La línea queda repartida entre los dos lotes.');
        $this->assertSame(0, bccomp($reparto[0]->cantidad, '3.000', 3));
        $this->assertSame(0, bccomp($reparto[0]->costo_unitario, '5.0000', 4), 'Cada porción guarda el costo de su lote.');
        $this->assertSame(0, bccomp($reparto[1]->costo_unitario, '6.0000', 4));
    }

    public function test_un_lote_vencido_nunca_se_toma(): void
    {
        $vencido = Lote::factory()->vencido()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '99.000', 'codigo_lote' => 'VENCIDO']);
        $vigente = $this->ingresar('10.000');

        $this->servicio->registrar($this->datos(), $this->vendedor);

        $this->assertSame(0, bccomp($vencido->refresh()->cantidad_actual, '99.000', 3), 'Vender vencido sería entregar mercadería vencida.');
        $this->assertSame(0, bccomp($vigente->refresh()->cantidad_actual, '8.000', 3));
    }

    /** No se vende parcialmente: si no alcanza, no sale nada. */
    public function test_stock_insuficiente_rechaza_la_venta_entera_sin_descontar(): void
    {
        $lote = $this->ingresar('1.000');

        try {
            $this->servicio->registrar($this->datos([], ['cantidad' => '5.000']), $this->vendedor);
            $this->fail('Se vendió más de lo que hay.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::STOCK_INSUFICIENTE, $error->codigo);
            $this->assertSame(0, bccomp($error->detalle['disponible'], '1.000', 3), 'El error informa cuánto había.');
        }

        $this->assertSame(0, bccomp($lote->refresh()->cantidad_actual, '1.000', 3));
        $this->assertSame(0, Venta::query()->count());
    }

    public function test_el_stock_vencido_no_cuenta_para_el_disponible(): void
    {
        Lote::factory()->vencido()->create(['producto_id' => $this->producto->id, 'cantidad_actual' => '99.000']);
        $this->ingresar('1.000');

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos([], ['cantidad' => '5.000']), $this->vendedor),
            CodigoDeError::STOCK_INSUFICIENTE
        );
    }

    // --- Importes e IGV (RF-011, RNF-006) ---

    public static function importesQueDebenCuadrar(): array
    {
        return [
            'dos unidades a 10' => ['2.000', '10.0000', '20.00'],
            'una unidad a 10' => ['1.000', '10.0000', '10.00'],
            'tres a 33,33' => ['3.000', '33.3300', '99.99'],
            'uno a 0,01' => ['1.000', '0.0100', '0.01'],
            'siete a 14,29' => ['7.000', '14.2900', '100.03'],
        ];
    }

    /** La base de datos exige que base + IGV sea exactamente el total. */
    #[DataProvider('importesQueDebenCuadrar')]
    public function test_base_mas_igv_es_exactamente_el_total(string $cantidad, string $precio, string $totalEsperado): void
    {
        $this->producto->update(['precio_menor' => $precio, 'precio_mayor' => $precio]);
        $this->ingresar('100.000');

        $venta = $this->servicio->registrar($this->datos([], ['cantidad' => $cantidad]), $this->vendedor);

        $this->assertSame(0, bccomp($venta->total, $totalEsperado, 2));
        $this->assertSame(
            0,
            bccomp(bcadd($venta->subtotal, $venta->igv, 2), $venta->total, 2),
            'Si base e IGV no suman el total, SUNAT rechaza el documento.'
        );
    }

    public function test_el_precio_se_copia_del_producto_segun_el_tipo(): void
    {
        $this->ingresar('100.000');

        $menor = $this->servicio->registrar($this->datos([], ['tipo_precio' => 'menor']), $this->vendedor);
        $mayor = $this->servicio->registrar($this->datos([], ['tipo_precio' => 'mayor']), $this->vendedor);

        $this->assertSame(0, bccomp($menor->lineas()->first()->precio_unitario, '10.0000', 4));
        $this->assertSame(0, bccomp($mayor->lineas()->first()->precio_unitario, '8.0000', 4));
    }

    /** Un precio enviado desde fuera dejaría que la caja venda a cualquier valor. */
    /**
     * No haber elegido el tipo de precio y haber elegido uno que no existe son
     * problemas distintos: al primero le falta un dato, el segundo trae uno
     * equivocado, y la pantalla no puede decir lo mismo en los dos casos.
     *
     * El servicio declaraba `TIPO_PRECIO_INVALIDO` como código del campo, y
     * eso alcanzaba también al campo ausente: respondía «el tipo de precio
     * debe ser menor o mayor» a quien no había enviado ninguno.
     */
    public function test_el_tipo_de_precio_ausente_no_se_confunde_con_uno_inexistente(): void
    {
        $this->ingresar('10.000', '5.0000', now()->addMonths(3)->format('Y-m-d'), 'L-001');

        $sinTipo = $this->errorAlVender(['producto_id' => $this->producto->id, 'cantidad' => '1.000']);
        $conTipoRaro = $this->errorAlVender([
            'producto_id' => $this->producto->id, 'cantidad' => '1.000', 'tipo_precio' => 'caro',
        ]);

        $this->assertSame(CodigoDeError::CAMPO_REQUERIDO, $sinTipo->codigo);
        $this->assertSame(CodigoDeError::TIPO_PRECIO_INVALIDO, $conTipoRaro->codigo);
    }

    /** @param  array<string, mixed>  $linea */
    private function errorAlVender(array $linea): ErrorDeDominio
    {
        try {
            $this->servicio->registrar(DatosDeEntrada::desde([
                'cliente_id' => $this->cliente->id,
                'tipo_comprobante' => '03',
                'metodo_pago' => 'efectivo',
                'lineas' => [$linea],
            ]), $this->vendedor);
        } catch (ErrorDeDominio $error) {
            return $error;
        }

        $this->fail('Se aceptó una línea que el contrato rechaza.');
    }

    public function test_un_precio_enviado_se_ignora(): void
    {
        $this->ingresar('100.000');

        $venta = $this->servicio->registrar($this->datos([], ['precio_unitario' => '0.0100']), $this->vendedor);

        $this->assertSame(0, bccomp($venta->lineas()->first()->precio_unitario, '10.0000', 4));
    }

    // --- Comprobante y correlativo (RF-013, RF-014) ---

    public function test_la_venta_crea_su_comprobante_pendiente(): void
    {
        $this->ingresar('100.000');

        $venta = $this->servicio->registrar($this->datos(), $this->vendedor);
        $comprobante = $venta->comprobante;

        $this->assertSame(Comprobante::ESTADO_PENDIENTE, $comprobante->estado);
        $this->assertSame('B001', $comprobante->serie);
        $this->assertSame(1, $comprobante->correlativo);
        $this->assertSame('B001-00000001', $comprobante->numeroFormateado());
    }

    public function test_los_correlativos_son_consecutivos_por_serie(): void
    {
        $this->ingresar('100.000');

        $primera = $this->servicio->registrar($this->datos(), $this->vendedor);
        $segunda = $this->servicio->registrar($this->datos(), $this->vendedor);

        $this->assertSame(1, $primera->comprobante->correlativo);
        $this->assertSame(2, $segunda->comprobante->correlativo);
    }

    public function test_factura_y_boleta_llevan_series_distintas(): void
    {
        $this->ingresar('100.000');
        $conRuc = Cliente::factory()->conRuc()->create(['direccion' => 'Av. Comercio 456']);

        $boleta = $this->servicio->registrar($this->datos(), $this->vendedor);
        $factura = $this->servicio->registrar($this->datos([
            'cliente_id' => $conRuc->id,
            'tipo_comprobante' => SerieComprobante::TIPO_FACTURA,
        ]), $this->vendedor);

        $this->assertSame('B001', $boleta->comprobante->serie);
        $this->assertSame('F001', $factura->comprobante->serie);
        $this->assertSame(1, $factura->comprobante->correlativo, 'Cada serie lleva su propia numeración.');
    }

    public function test_sin_serie_configurada_se_rechaza_antes_de_tocar_stock(): void
    {
        SerieComprobante::query()->delete();
        $lote = $this->ingresar('100.000');

        $this->assertRechaza(fn () => $this->servicio->registrar($this->datos(), $this->vendedor), CodigoDeError::SERIE_NO_CONFIGURADA);

        $this->assertSame(0, bccomp($lote->refresh()->cantidad_actual, '100.000', 3), 'No se descontó nada.');
    }

    // --- Reglas del momento de emitir (heredadas de S-03-B) ---

    public function test_una_factura_exige_ruc(): void
    {
        $this->ingresar('100.000');

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos(['tipo_comprobante' => SerieComprobante::TIPO_FACTURA]), $this->vendedor),
            CodigoDeError::FACTURA_REQUIERE_RUC
        );
    }

    public function test_una_factura_exige_direccion_del_cliente(): void
    {
        $this->ingresar('100.000');
        $sinDireccion = Cliente::factory()->conRuc()->create(['direccion' => null]);

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos([
                'cliente_id' => $sinDireccion->id,
                'tipo_comprobante' => SerieComprobante::TIPO_FACTURA,
            ]), $this->vendedor),
            CodigoDeError::FACTURA_REQUIERE_RUC
        );
    }

    /** El tope es regla de venta, no de cliente: depende del importe. */
    public function test_una_boleta_sobre_el_tope_exige_identificar_al_cliente(): void
    {
        $this->producto->update(['precio_menor' => '100.0000']);
        $this->ingresar('100.000');
        $anonimo = Cliente::factory()->sinDocumento()->create(['nombre' => 'Público general']);

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos(['cliente_id' => $anonimo->id], ['cantidad' => '8.000']), $this->vendedor),
            CodigoDeError::BOLETA_REQUIERE_DOCUMENTO
        );
    }

    public function test_una_boleta_bajo_el_tope_no_exige_documento(): void
    {
        $this->producto->update(['precio_menor' => '100.0000']);
        $this->ingresar('100.000');
        $anonimo = Cliente::factory()->sinDocumento()->create(['nombre' => 'Público general']);

        $venta = $this->servicio->registrar($this->datos(['cliente_id' => $anonimo->id], ['cantidad' => '6.000']), $this->vendedor);

        $this->assertSame(0, bccomp($venta->total, '600.00', 2));
    }

    public function test_el_tope_es_configurable(): void
    {
        config(['venta.tope_boleta_sin_documento' => '100.00']);
        $this->producto->update(['precio_menor' => '100.0000']);
        $this->ingresar('100.000');
        $anonimo = Cliente::factory()->sinDocumento()->create(['nombre' => 'Público general']);

        $this->assertRechaza(
            fn () => $this->servicio->registrar($this->datos(['cliente_id' => $anonimo->id], ['cantidad' => '2.000']), $this->vendedor),
            CodigoDeError::BOLETA_REQUIERE_DOCUMENTO
        );
    }

    // --- Atomicidad (RNF-003) ---

    /**
     * Si algo falla, no puede quedar ni venta, ni stock descontado, ni
     * correlativo consumido. Cada resto por separado es un problema distinto y
     * ninguno se arregla solo.
     */
    public function test_si_una_linea_falla_no_queda_nada(): void
    {
        $lote = $this->ingresar('100.000');
        $otro = Producto::factory()->create();

        $datos = $this->datos();
        $lineas = $datos->valor('lineas');
        $lineas[] = ['producto_id' => $otro->id, 'cantidad' => '1.000', 'tipo_precio' => 'menor'];

        try {
            $this->servicio->registrar(DatosDeEntrada::desde(['lineas' => $lineas] + $datos->todos()), $this->vendedor);
            $this->fail('Se registró una venta con una línea sin stock.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::STOCK_INSUFICIENTE, $error->codigo);
        }

        $this->assertSame(0, Venta::query()->count());
        $this->assertSame(0, Comprobante::query()->count());
        $this->assertSame(0, bccomp($lote->refresh()->cantidad_actual, '100.000', 3), 'La primera línea tampoco descontó.');
        $this->assertSame(0, SerieComprobante::query()->where('serie', 'B001')->value('correlativo_actual'), 'El correlativo no se consumió.');
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
