<?php

namespace Tests\Feature\Dominios\Ventas;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Servicios\SerieComprobanteService;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use App\Dominios\Ventas\Servicios\VentaService;
use Database\Seeders\SeriesComprobanteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alcance y proyección son dos preguntas distintas sobre el mismo recurso:
 * quién puede pedir la venta, y qué campos viajan dentro. El alcance ya estaba
 * cubierto; esta es la segunda, que es la que se olvida.
 *
 * El costo es información de negociación con el proveedor. Con el precio de
 * venta en la misma respuesta, el margen sale por resta, así que dejarlo pasar
 * equivale a publicar el margen (contrato de ventas, enmienda de Arquitectura
 * 2026-08-20).
 */
final class ProyeccionDeLaVentaTest extends TestCase
{
    use RefreshDatabase;

    private VentaService $servicio;

    private Producto $producto;

    private Usuario $vendedor;

    private const COSTO = '5.0000';

    protected function setUp(): void
    {
        parent::setUp();

        $inventario = new InventarioService(new AuditoriaService);
        $this->servicio = new VentaService($inventario, new SerieComprobanteService);
        $this->vendedor = Usuario::factory()->create();
        $this->producto = Producto::factory()->create(['precio_menor' => '10.0000', 'precio_mayor' => '8.0000']);

        $this->seed(SeriesComprobanteSeeder::class);

        $inventario->ingresar(
            (int) $this->producto->id, '100.000', self::COSTO, 'L-001',
            now()->addMonths(6)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA, 1, (int) $this->vendedor->id,
        );
    }

    private function registrar(Usuario $actor): int
    {
        $venta = $this->servicio->registrar(DatosDeEntrada::desde([
            'cliente_id' => Cliente::factory()->create(['direccion' => 'Av. Siempre Viva 123'])->id,
            'tipo_comprobante' => '03',
            'metodo_pago' => 'efectivo',
            'lineas' => [[
                'producto_id' => $this->producto->id,
                'cantidad' => '2.000',
                'tipo_precio' => 'menor',
            ]],
        ]), $actor);

        return (int) $venta->id;
    }

    /**
     * Busca el costo en toda la respuesta, no en los dos sitios donde hoy
     * está: si mañana aparece en un tercero, esta prueba lo ve igual.
     */
    public function test_la_venta_del_vendedor_no_lleva_el_costo_por_ningun_camino(): void
    {
        $id = $this->registrar($this->vendedor);

        $respuesta = $this->servicio->encontrar($id, $this->vendedor);

        $this->assertSame([], self::rutasConCosto($respuesta), 'La respuesta del vendedor no puede llevar el costo.');
        $this->assertStringNotContainsString(
            self::COSTO,
            (string) json_encode($respuesta),
            'El valor del costo aparece en la respuesta del vendedor.'
        );
    }

    /**
     * El complemento: sin esto, una proyección que devolviera la venta vacía
     * pasaría la prueba de arriba sin proyectar nada.
     */
    public function test_el_administrador_si_ve_el_costo_del_reparto_y_del_lote(): void
    {
        $id = $this->registrar($this->vendedor);

        $respuesta = $this->servicio->encontrar($id, Usuario::factory()->administrador()->create());
        $porcion = $respuesta['lineas'][0]['reparto'][0];

        $this->assertSame(0, bccomp($porcion['costo_unitario'], self::COSTO, 4));
        $this->assertSame(0, bccomp($porcion['costo_unitario_lote'], self::COSTO, 4));
    }

    /**
     * Fija el conjunto **exacto** de claves que recibe el vendedor, no que
     * estén las que esperamos.
     *
     * Buscar el costo por su nombre o por su valor no alcanza: un campo que se
     * llame de otro modo y venga redondeado lleva el costo adentro y pasa por
     * delante de las dos búsquedas. Contra eso no hay prueba de contenido que
     * sirva, porque siempre hay una transformación más.
     *
     * Enumerar el conjunto invierte la carga: cualquier campo nuevo hace
     * fallar la prueba y obliga a mirarlo, se llame como se llame y valga lo
     * que valga. Es la misma forma que usa la proyección del código —enumerar
     * lo que sale en vez de ocultar lo que no debe salir—, y tenerla en los dos
     * lados obliga a que el descuido ocurra dos veces.
     */
    public function test_el_vendedor_recibe_exactamente_estas_claves_y_ninguna_mas(): void
    {
        $id = $this->registrar($this->vendedor);

        $respuesta = $this->servicio->encontrar($id, $this->vendedor);

        $this->assertSame(
            ['id', 'fecha', 'subtotal', 'igv', 'total', 'metodo_pago', 'cliente', 'comprobante', 'lineas'],
            array_keys($respuesta),
            'Cambió el conjunto de campos de la venta.'
        );

        $this->assertSame(
            ['id', 'tipo_documento', 'numero_documento', 'nombre'],
            array_keys($respuesta['cliente']),
            'Cambió el conjunto de campos del cliente.'
        );

        $this->assertSame(
            ['tipo_comprobante', 'serie', 'correlativo', 'fecha_emision', 'estado'],
            array_keys($respuesta['comprobante']),
            'Cambió el conjunto de campos del comprobante.'
        );

        $linea = $respuesta['lineas'][0];

        $this->assertSame(
            ['id', 'producto', 'cantidad', 'tipo_precio', 'precio_unitario', 'importe', 'reparto'],
            array_keys($linea),
            'Cambió el conjunto de campos de la línea.'
        );

        $this->assertSame(
            ['id', 'codigo', 'nombre'],
            array_keys($linea['producto']),
            'Cambió el conjunto de campos del producto.'
        );

        $this->assertSame(
            ['lote_id', 'codigo_lote', 'fecha_vencimiento', 'cantidad'],
            array_keys($linea['reparto'][0]),
            'Cambió el conjunto de campos del reparto: cualquier campo nuevo hay que mirarlo antes de dejarlo salir.'
        );
    }

    /** El conjunto del administrador se fija aparte: es el que sí lleva costo. */
    public function test_el_administrador_recibe_exactamente_estas_claves_en_el_reparto(): void
    {
        $id = $this->registrar($this->vendedor);

        $respuesta = $this->servicio->encontrar($id, Usuario::factory()->administrador()->create());

        $this->assertSame(
            ['lote_id', 'codigo_lote', 'fecha_vencimiento', 'cantidad', 'costo_unitario', 'costo_unitario_lote'],
            array_keys($respuesta['lineas'][0]['reparto'][0]),
            'Cambió el conjunto de campos del reparto para administrador.'
        );
    }

    /** La proyección sigue llevando lo que el vendedor sí necesita de su venta. */
    public function test_la_proyeccion_conserva_lo_que_el_vendedor_necesita(): void
    {
        $id = $this->registrar($this->vendedor);

        $respuesta = $this->servicio->encontrar($id, $this->vendedor);
        $linea = $respuesta['lineas'][0];

        $this->assertSame(0, bccomp($respuesta['total'], '20.00', 2));
        $this->assertSame('PENDIENTE', $respuesta['comprobante']['estado']);
        $this->assertSame('B001', $respuesta['comprobante']['serie']);
        $this->assertSame($this->producto->codigo, $linea['producto']['codigo']);
        $this->assertSame(0, bccomp($linea['precio_unitario'], '10.0000', 4));
        $this->assertSame(0, bccomp($linea['reparto'][0]['cantidad'], '2.000', 3));
        $this->assertSame('L-001', $linea['reparto'][0]['codigo_lote']);
    }

    /**
     * Rutas, recorriendo la respuesta entera, en las que aparece una clave que
     * nombre un costo.
     *
     * @param  mixed  $valor
     * @return list<string>
     */
    private static function rutasConCosto($valor, string $ruta = ''): array
    {
        if (! is_array($valor)) {
            return [];
        }

        $encontradas = [];

        foreach ($valor as $clave => $hijo) {
            $rutaHija = $ruta === '' ? (string) $clave : $ruta.'.'.$clave;

            if (is_string($clave) && str_contains($clave, 'costo')) {
                $encontradas[] = $rutaHija;
            }

            $encontradas = array_merge($encontradas, self::rutasConCosto($hijo, $rutaHija));
        }

        return $encontradas;
    }
}
