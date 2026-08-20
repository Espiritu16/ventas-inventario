<?php

namespace Tests\Feature\Dominios\Ventas;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Idempotencia\OperacionIdempotente;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Servicios\SerieComprobanteService;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Ventas\Modelos\Venta;
use App\Dominios\Ventas\Servicios\VentaService;
use Database\Seeders\SeriesComprobanteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class IdempotenciaYConsultaTest extends TestCase
{
    use RefreshDatabase;

    private VentaService $servicio;

    private Producto $producto;

    private Cliente $cliente;

    private Usuario $vendedor;

    private const CLAVE = '9f1b6c2e-8a4d-4f3b-9c1e-2d5a7b8c9e0f';

    protected function setUp(): void
    {
        parent::setUp();

        $inventario = new InventarioService(new AuditoriaService);
        $this->servicio = new VentaService($inventario, new SerieComprobanteService);

        $this->producto = Producto::factory()->create(['precio_menor' => '10.0000', 'precio_mayor' => '8.0000']);
        $this->cliente = Cliente::factory()->create(['direccion' => 'Av. Siempre Viva 123']);
        $this->vendedor = Usuario::factory()->create();

        $this->seed(SeriesComprobanteSeeder::class);

        $inventario->ingresar(
            (int) $this->producto->id, '500.000', '5.0000', 'L-001',
            now()->addMonths(6)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA, 1, (int) $this->vendedor->id,
        );
    }

    private function datos(array $cambios = [], array $linea = []): DatosDeEntrada
    {
        return DatosDeEntrada::desde(array_merge([
            'cliente_id' => $this->cliente->id,
            'tipo_comprobante' => '03',
            'metodo_pago' => 'efectivo',
            'lineas' => [array_merge([
                'producto_id' => $this->producto->id,
                'cantidad' => '2.000',
                'tipo_precio' => 'menor',
            ], $linea)],
        ], $cambios));
    }

    // --- Idempotencia (UT-05) ---

    /** Un doble clic no puede descontar stock dos veces ni gastar dos correlativos. */
    public function test_la_misma_clave_devuelve_la_venta_original_sin_descontar_de_nuevo(): void
    {
        $primera = $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);
        $saldoTrasLaPrimera = Lote::query()->value('cantidad_actual');

        $segunda = $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);

        $this->assertSame($primera->id, $segunda->id, 'Debe devolver la misma venta.');
        $this->assertSame(1, Venta::query()->count());
        $this->assertSame(0, bccomp(Lote::query()->value('cantidad_actual'), $saldoTrasLaPrimera, 3));
        $this->assertSame(1, (int) \DB::table('series_comprobante')->where('serie', 'B001')->value('correlativo_actual'));
    }

    /**
     * La misma clave con datos distintos no es un reintento: es un error de
     * quien la envía. Devolverle la venta anterior le daría por buena una
     * operación que no pidió.
     */
    public function test_la_misma_clave_con_datos_distintos_se_rechaza(): void
    {
        $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);

        try {
            $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos([], ['cantidad' => '5.000']), $this->vendedor);
            $this->fail('Se aceptó la misma clave con otra huella.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::OPERACION_DUPLICADA, $error->codigo);
        }

        $this->assertSame(1, Venta::query()->count());
    }

    /** Las mismas líneas en otro orden son la misma venta. */
    public function test_el_orden_de_las_lineas_no_cambia_la_huella(): void
    {
        $otro = Producto::factory()->create(['precio_menor' => '4.0000', 'precio_mayor' => '3.0000']);
        (new InventarioService(new AuditoriaService))->ingresar(
            (int) $otro->id, '100.000', '2.0000', 'L-002',
            now()->addMonths(6)->format('Y-m-d'), MovimientoInventario::ORIGEN_COMPRA, 1, (int) $this->vendedor->id,
        );

        $a = ['producto_id' => $this->producto->id, 'cantidad' => '2.000', 'tipo_precio' => 'menor'];
        $b = ['producto_id' => $otro->id, 'cantidad' => '1.000', 'tipo_precio' => 'menor'];

        $primera = $this->servicio->registrarUnaSolaVez(self::CLAVE, DatosDeEntrada::desde([
            'cliente_id' => $this->cliente->id, 'tipo_comprobante' => '03', 'metodo_pago' => 'efectivo',
            'lineas' => [$a, $b],
        ]), $this->vendedor);

        $segunda = $this->servicio->registrarUnaSolaVez(self::CLAVE, DatosDeEntrada::desde([
            'cliente_id' => $this->cliente->id, 'tipo_comprobante' => '03', 'metodo_pago' => 'efectivo',
            'lineas' => [$b, $a],
        ]), $this->vendedor);

        $this->assertSame($primera->id, $segunda->id);
    }

    public static function clavesMalFormadas(): array
    {
        return [
            'vacía' => [''],
            'no es UUID' => ['clave-de-operacion'],
            'UUID versión 1' => ['9f1b6c2e-8a4d-1f3b-9c1e-2d5a7b8c9e0f'],
            'con mayúsculas' => ['9F1B6C2E-8A4D-4F3B-9C1E-2D5A7B8C9E0F'],
            'demasiado corta' => ['9f1b6c2e-8a4d-4f3b-9c1e'],
        ];
    }

    #[DataProvider('clavesMalFormadas')]
    public function test_una_clave_mal_formada_se_rechaza(string $clave): void
    {
        try {
            $this->servicio->registrarUnaSolaVez($clave, $this->datos(), $this->vendedor);
            $this->fail("Se aceptó la clave «{$clave}».");
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }

        $this->assertSame(0, Venta::query()->count());
    }

    /** Una clave vencida no se reprocesa: se rechaza y se abre la venta de nuevo. */
    public function test_una_clave_expirada_se_rechaza(): void
    {
        $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);

        OperacionIdempotente::query()->where('clave', self::CLAVE)->update(['expira_en' => now()->subMinute()]);

        try {
            $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);
            $this->fail('Se aceptó una clave expirada.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::OPERACION_DUPLICADA, $error->codigo);
        }
    }

    /**
     * Si la venta falla, la clave se libera: fue un intento fallido, no una
     * operación ya hecha, y quien reintente debe poder hacerlo.
     */
    public function test_una_venta_fallida_libera_su_clave(): void
    {
        try {
            $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos([], ['cantidad' => '9999.000']), $this->vendedor);
            $this->fail('Se registró una venta sin stock.');
        } catch (ErrorDeDominio) {
            // esperado
        }

        $this->assertSame(0, OperacionIdempotente::query()->count());

        $venta = $this->servicio->registrarUnaSolaVez(self::CLAVE, $this->datos(), $this->vendedor);
        $this->assertNotNull($venta->id, 'El reintento con la misma clave debe poder registrarse.');
    }

    // --- Fecha de emisión: fecha civil de Lima (nota heredada de S-00) ---

    public static function instantesDeBorde(): array
    {
        return [
            'medianoche de Lima' => ['2026-08-15 00:00:00', '2026-08-15'],
            'las 23:40 de Lima' => ['2026-08-15 23:40:00', '2026-08-15'],
            'un segundo antes de medianoche' => ['2026-08-15 23:59:59', '2026-08-15'],
            'mediodía' => ['2026-08-15 12:00:00', '2026-08-15'],
        ];
    }

    /**
     * Una venta cerrada a las 23:40 no puede quedar emitida con la fecha del
     * día siguiente: el correlativo y la fecha tienen que ser coherentes ante
     * SUNAT, y el resumen diario de boletas se agrupa por este campo, así que
     * un corrimiento partiría un día en dos resúmenes.
     */
    #[DataProvider('instantesDeBorde')]
    public function test_la_fecha_de_emision_es_el_dia_civil_de_lima(string $horaDeLima, string $esperada): void
    {
        Carbon::setTestNow(Carbon::parse($horaDeLima, config('app.timezone_visualizacion')));

        $venta = $this->servicio->registrar($this->datos(), $this->vendedor);

        $this->assertSame($esperada, $venta->comprobante->fecha_emision->format('Y-m-d'));

        Carbon::setTestNow();
    }

    // --- Consulta con alcance del vendedor (UT-06) ---

    public function test_el_vendedor_no_ve_ventas_ajenas_ni_en_el_total(): void
    {
        $otro = Usuario::factory()->create();

        $this->servicio->registrar($this->datos(), $this->vendedor);
        $this->servicio->registrar($this->datos(), $otro);
        $this->servicio->registrar($this->datos(), $otro);

        $pagina = $this->servicio->listar($this->vendedor);

        $this->assertCount(1, $pagina->items());
        $this->assertSame(1, $pagina->total(), 'El acote también alcanza al total del paginado.');
    }

    public function test_el_administrador_ve_todas(): void
    {
        $this->servicio->registrar($this->datos(), $this->vendedor);
        $this->servicio->registrar($this->datos(), Usuario::factory()->create());

        $this->assertSame(2, $this->servicio->listar(Usuario::factory()->administrador()->create())->total());
    }

    /**
     * Una venta ajena responde igual que una inexistente: distinguirlas le
     * diría al vendedor que existe algo que no puede ver, y convertiría el
     * identificador en un modo de contar cuántas ventas hay.
     */
    public function test_una_venta_ajena_responde_como_inexistente(): void
    {
        $ajena = $this->servicio->registrar($this->datos(), Usuario::factory()->create());

        $errorAjena = null;
        $errorInexistente = null;

        try {
            $this->servicio->encontrar((int) $ajena->id, $this->vendedor);
        } catch (ErrorDeDominio $e) {
            $errorAjena = $e;
        }

        try {
            $this->servicio->encontrar(999999, $this->vendedor);
        } catch (ErrorDeDominio $e) {
            $errorInexistente = $e;
        }

        $this->assertNotNull($errorAjena, 'El vendedor no puede acceder a una venta ajena.');
        $this->assertSame($errorInexistente->codigo, $errorAjena->codigo);
        $this->assertSame($errorInexistente->getMessage(), $errorAjena->getMessage());
    }

    public function test_el_vendedor_si_accede_a_la_propia(): void
    {
        $propia = $this->servicio->registrar($this->datos(), $this->vendedor);

        $encontrada = $this->servicio->encontrar((int) $propia->id, $this->vendedor);

        $this->assertSame($propia->id, $encontrada->id);
        $this->assertCount(1, $encontrada->lineas);
        $this->assertNotNull($encontrada->comprobante);
    }
}
