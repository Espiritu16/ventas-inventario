<?php

namespace Tests\Feature\Dominios\Ventas;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Compartido\Fechas\RangoDeFechas;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Comprobantes\Modelos\Comprobante;
use App\Dominios\Comprobantes\Modelos\SerieComprobante;
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

/**
 * Reporte de ventas del período (RF-020) y de utilidad real (RF-021).
 *
 * Van juntos porque leen el mismo hecho desde dos alturas: el reporte de
 * ventas mira lo que entró, el de utilidad mira además lo que costó. Y los dos
 * dependen de la misma conversión de fecha civil a instante, que es donde un
 * reporte pierde el último día sin que nadie lo note.
 */
final class ReportesDeVentasTest extends TestCase
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

        $this->producto = Producto::factory()->create([
            'nombre' => 'Leche entera',
            'precio_menor' => '10.0000',
            'precio_mayor' => '8.0000',
        ]);
        $this->cliente = Cliente::factory()->create();
        $this->vendedor = Usuario::factory()->create();

        $this->seed(SeriesComprobanteSeeder::class);
    }

    private function ingresar(string $cantidad, string $costo, string $codigoLote, int $venceEnMeses = 6, ?Producto $producto = null): void
    {
        $this->inventario->ingresar(
            (int) ($producto ?? $this->producto)->id,
            $cantidad,
            $costo,
            $codigoLote,
            now()->addMonths($venceEnMeses)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA,
            1,
            (int) $this->vendedor->id,
        );
    }

    /** @param  array<string, mixed>  $cambios */
    private function vender(string $cantidad = '2.000', array $cambios = [], ?Producto $producto = null): Venta
    {
        return $this->servicio->registrar(DatosDeEntrada::desde(array_merge([
            'cliente_id' => $this->cliente->id,
            'tipo_comprobante' => SerieComprobante::TIPO_BOLETA,
            'metodo_pago' => 'efectivo',
            'lineas' => [[
                'producto_id' => ($producto ?? $this->producto)->id,
                'cantidad' => $cantidad,
                'tipo_precio' => 'menor',
            ]],
        ], $cambios)), $this->vendedor);
    }

    private function hoy(): string
    {
        return now()->timezone(config('app.timezone_visualizacion'))->format('Y-m-d');
    }

    // --- RF-020: reporte de ventas del período ---

    public function test_el_total_coincide_con_la_suma_de_las_ventas_del_rango(): void
    {
        $this->ingresar('100.000', '5.0000', 'L-001');
        $primera = $this->vender('2.000');
        $segunda = $this->vender('3.000');

        $reporte = $this->servicio->reporteVentas($this->hoy(), $this->hoy());

        $esperado = bcadd($primera->total, $segunda->total, 2);

        $this->assertSame(2, $reporte['cantidad']);
        $this->assertSame(0, bccomp($reporte['total'], $esperado, 2));
        $this->assertSame(0, bccomp($esperado, '50.00', 2), '5 unidades a S/ 10.');
    }

    public function test_las_ventas_de_fuera_del_rango_no_entran(): void
    {
        $this->ingresar('100.000', '5.0000', 'L-001');
        $this->vender('2.000');

        $ayer = now()->timezone(config('app.timezone_visualizacion'))->subDay()->format('Y-m-d');
        $reporte = $this->servicio->reporteVentas($ayer, $ayer);

        $this->assertSame(0, $reporte['cantidad']);
        $this->assertSame(0, bccomp($reporte['total'], '0.00', 2));
        $this->assertSame([], $reporte['ventas']);
    }

    /**
     * La venta de las 23:40 de Lima ya ocurrió en el día siguiente en UTC. Un
     * reporte que compare el instante contra la fecha civil sin convertirla se
     * come las últimas cinco horas de cada día — y justo las de más caja.
     */
    public function test_una_venta_de_las_ultimas_horas_del_dia_entra_en_ese_dia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 23:40:00', config('app.timezone_visualizacion')));

        $this->ingresar('100.000', '5.0000', 'L-001');
        $venta = $this->vender('2.000');

        $this->assertSame('2026-08-21', $venta->fecha->format('Y-m-d'), 'En UTC ya es el día siguiente.');

        $reporte = $this->servicio->reporteVentas('2026-08-20', '2026-08-20');

        $this->assertSame(1, $reporte['cantidad'], 'Pero para quien vendió fue el día 20.');
        $this->assertSame(0, bccomp($reporte['total'], $venta->total, 2));
    }

    public function test_desglosa_por_tipo_de_comprobante_y_por_medio_de_pago(): void
    {
        $conRuc = Cliente::factory()->conRuc()->create(['direccion' => 'Av. Siempre Viva 123']);

        $this->ingresar('100.000', '5.0000', 'L-001');
        $this->vender('2.000');
        $this->vender('3.000', ['metodo_pago' => 'tarjeta']);
        $this->vender('4.000', [
            'cliente_id' => $conRuc->id,
            'tipo_comprobante' => SerieComprobante::TIPO_FACTURA,
            'metodo_pago' => 'tarjeta',
        ]);

        $reporte = $this->servicio->reporteVentas($this->hoy(), $this->hoy());

        $this->assertSame([
            ['tipo_comprobante' => SerieComprobante::TIPO_FACTURA, 'cantidad' => 1, 'total' => '40.00'],
            ['tipo_comprobante' => SerieComprobante::TIPO_BOLETA, 'cantidad' => 2, 'total' => '50.00'],
        ], $reporte['por_comprobante']);

        $this->assertSame([
            ['metodo_pago' => 'efectivo', 'cantidad' => 1, 'total' => '20.00'],
            ['metodo_pago' => 'tarjeta', 'cantidad' => 2, 'total' => '70.00'],
        ], $reporte['por_metodo_pago']);

        $this->assertSame(0, bccomp($reporte['total'], '90.00', 2));
        $this->assertDesgloseSumaElTotal($reporte);
    }

    public function test_las_ventas_con_comprobante_rechazado_se_distinguen_sin_salir_del_total(): void
    {
        $this->ingresar('100.000', '5.0000', 'L-001');
        $buena = $this->vender('2.000');
        $rechazada = $this->vender('3.000');

        Comprobante::query()
            ->where('venta_id', $rechazada->id)
            ->update(['estado' => Comprobante::ESTADO_RECHAZADO]);

        $reporte = $this->servicio->reporteVentas($this->hoy(), $this->hoy());
        $porId = collect($reporte['ventas'])->keyBy('id');

        $this->assertTrue($porId[$rechazada->id]['rechazado']);
        $this->assertFalse($porId[$buena->id]['rechazado']);
        $this->assertSame(Comprobante::ESTADO_RECHAZADO, $porId[$rechazada->id]['comprobante']['estado']);

        $this->assertSame(1, $reporte['rechazadas']['cantidad']);
        $this->assertSame(0, bccomp($reporte['rechazadas']['total'], $rechazada->total, 2));

        // La venta ocurrió y el dinero entró: lo que quedó mal es el documento.
        $this->assertSame(
            0,
            bccomp($reporte['total'], bcadd($buena->total, $rechazada->total, 2), 2),
            'Un comprobante rechazado se señala, no se descuenta.'
        );
    }

    public function test_el_detalle_trae_lo_que_identifica_cada_venta(): void
    {
        $this->ingresar('100.000', '5.0000', 'L-001');
        $venta = $this->vender('2.000');

        $fila = $this->servicio->reporteVentas($this->hoy(), $this->hoy())['ventas'][0];

        $this->assertSame((int) $venta->id, (int) $fila['id']);
        $this->assertSame($this->cliente->nombre, $fila['cliente']);
        $this->assertSame('efectivo', $fila['metodo_pago']);
        $this->assertSame('B001', $fila['comprobante']['serie']);
        $this->assertSame(1, $fila['comprobante']['correlativo']);
    }

    // --- RF-021: utilidad con costo real por lote (ADR-0004) ---

    /**
     * El caso que separa el costo real de un promedio.
     *
     * Dos lotes del mismo producto a S/ 2 y S/ 5, y una venta de 12 unidades
     * que se lleva el primero entero y dos del segundo: el costo real es
     * `10×2 + 2×5 = 30`. Cualquier promedio de los dos costos —simple o
     * ponderado por existencia, que acá coinciden en 3,5— daría `42`. Con un
     * solo lote los dos números serían iguales y la prueba no distinguiría
     * nada.
     *
     * De paso fija que el costo **no** se calcula uniendo `detalle_ventas` con
     * `detalle_venta_lotes` en una sola consulta: esa unión multiplica la línea
     * por sus dos porciones y el ingreso saldría 240 en vez de 120.
     */
    public function test_el_costo_es_la_suma_de_las_porciones_de_lote_y_no_un_promedio(): void
    {
        $this->ingresar('10.000', '2.0000', 'L-BARATO', 2);
        $this->ingresar('10.000', '5.0000', 'L-CARO', 9);

        $this->vender('12.000');

        $reporte = $this->servicio->reporteUtilidad($this->hoy(), $this->hoy());

        $this->assertSame(0, bccomp($reporte['ingreso'], '120.00', 2), '12 unidades a S/ 10.');
        $this->assertSame(0, bccomp($reporte['costo'], '30.00', 2), '10 a S/ 2 más 2 a S/ 5.');
        $this->assertSame(0, bccomp($reporte['utilidad'], '90.00', 2));
        $this->assertNotSame(0, bccomp($reporte['costo'], '42.00', 2), 'El promedio de S/ 3,50 daría 42.');
    }

    /**
     * El costo del reporte es el que se congeló al salir, no el que el lote
     * tenga después: si el proveedor sube el precio, la utilidad de una venta
     * de la semana pasada no cambia.
     */
    public function test_el_costo_no_sigue_al_lote_despues_de_la_venta(): void
    {
        $this->ingresar('10.000', '2.0000', 'L-BARATO', 2);
        $this->vender('4.000');

        $antes = $this->servicio->reporteUtilidad($this->hoy(), $this->hoy())['costo'];

        Lote::query()
            ->where('codigo_lote', 'L-BARATO')
            ->update(['costo_unitario' => '50.0000']);

        $this->assertSame(0, bccomp($antes, '8.00', 2));
        $this->assertSame(
            0,
            bccomp($this->servicio->reporteUtilidad($this->hoy(), $this->hoy())['costo'], '8.00', 2),
            'El reporte lee el costo congelado en el reparto, no el del lote de hoy.'
        );
    }

    public function test_desglosa_por_producto_y_las_filas_suman_el_total(): void
    {
        $otro = Producto::factory()->create(['nombre' => 'Arroz', 'precio_menor' => '4.0000', 'precio_mayor' => '3.0000']);

        $this->ingresar('50.000', '2.0000', 'L-LECHE');
        $this->ingresar('50.000', '1.0000', 'L-ARROZ', 6, $otro);

        $this->vender('3.000');
        $this->vender('5.000', [], $otro);

        $reporte = $this->servicio->reporteUtilidad($this->hoy(), $this->hoy());

        $this->assertSame(['Arroz', 'Leche entera'], array_column($reporte['por_producto'], 'nombre'), 'Ordenado por nombre.');

        $ingreso = '0.00';
        $costo = '0.00';
        $utilidad = '0.00';

        foreach ($reporte['por_producto'] as $fila) {
            $this->assertSame(0, bccomp($fila['utilidad'], bcsub($fila['ingreso'], $fila['costo'], 2), 2));
            $ingreso = bcadd($ingreso, $fila['ingreso'], 2);
            $costo = bcadd($costo, $fila['costo'], 2);
            $utilidad = bcadd($utilidad, $fila['utilidad'], 2);
        }

        $this->assertSame(0, bccomp($ingreso, $reporte['ingreso'], 2));
        $this->assertSame(0, bccomp($costo, $reporte['costo'], 2));
        $this->assertSame(0, bccomp($utilidad, $reporte['utilidad'], 2));
        $this->assertSame(0, bccomp($reporte['ingreso'], '50.00', 2), '3 leches a 10 más 5 arroces a 4.');
        $this->assertSame(0, bccomp($reporte['costo'], '11.00', 2), '3 a S/ 2 más 5 a S/ 1.');
    }

    public function test_el_filtro_por_producto_acota_ingreso_y_costo(): void
    {
        $otro = Producto::factory()->create(['nombre' => 'Arroz', 'precio_menor' => '4.0000', 'precio_mayor' => '3.0000']);

        $this->ingresar('50.000', '2.0000', 'L-LECHE');
        $this->ingresar('50.000', '1.0000', 'L-ARROZ', 6, $otro);

        $this->vender('3.000');
        $this->vender('5.000', [], $otro);

        $reporte = $this->servicio->reporteUtilidad($this->hoy(), $this->hoy(), (int) $otro->id);

        $this->assertCount(1, $reporte['por_producto']);
        $this->assertSame('Arroz', $reporte['por_producto'][0]['nombre']);
        $this->assertSame(0, bccomp($reporte['ingreso'], '20.00', 2));
        $this->assertSame(0, bccomp($reporte['costo'], '5.00', 2));
        $this->assertSame(0, bccomp($reporte['utilidad'], '15.00', 2));
    }

    /**
     * El rango del reporte de utilidad se acota **dos veces**: una en el
     * agregado de ingreso y otra en el de costo, que corren sobre
     * granularidades distintas y por eso no pueden compartir la condición.
     * Nada comprobaba ninguna de las dos, y la forma asimétrica es la
     * peligrosa: ingreso de un período contra costo de otro da una utilidad
     * falsa sin que ninguna cifra se vea rara.
     *
     * **El reloj se fija cruzando el límite de zona horaria a propósito.**
     * Lima es UTC-5, así que una venta de las 23:40 ya pertenece al día
     * siguiente en UTC: ahí es donde un rango mal construido se rompe. Con el
     * reloj en la mañana la prueba pasaría por la hora de la corrida y no por
     * el mecanismo, que es exactamente como este hueco sobrevivió.
     */
    public function test_el_ingreso_y_el_costo_del_reporte_de_utilidad_se_acotan_al_mismo_rango(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 23:40:00', config('app.timezone_visualizacion')));

        $this->ingresar('100.000', '2.0000', 'L-001');
        $delVeinte = $this->vender('3.000');

        Carbon::setTestNow(Carbon::parse('2026-08-21 23:40:00', config('app.timezone_visualizacion')));

        $delVeintiuno = $this->vender('5.000');

        $this->assertSame('2026-08-21', $delVeinte->fecha->format('Y-m-d'), 'En UTC la venta del 20 cayó en el 21.');
        $this->assertSame('2026-08-22', $delVeintiuno->fecha->format('Y-m-d'));

        $reporte = $this->servicio->reporteUtilidad('2026-08-20', '2026-08-20');

        $this->assertSame(0, bccomp($reporte['ingreso'], '30.00', 2), '3 unidades a S/ 10; con las 5 del día siguiente adentro serían 80.');
        $this->assertSame(0, bccomp($reporte['costo'], '6.00', 2), '3 unidades a S/ 2; con las del día siguiente adentro serían 16.');
        $this->assertSame(0, bccomp($reporte['utilidad'], '24.00', 2));

        $this->assertCount(1, $reporte['por_producto']);
        $fila = $reporte['por_producto'][0];

        $this->assertSame(0, bccomp($fila['cantidad'], '3.000', 3));
        $this->assertSame(0, bccomp($fila['ingreso'], '30.00', 2), 'La fila arrastra el mismo recorte que el total.');
        $this->assertSame(0, bccomp($fila['costo'], '6.00', 2));
    }

    /**
     * El mismo borde por el otro lado: la venta de las 23:40 es del día 20 para
     * quien la hizo, aunque su instante en UTC sea del 21. Un reporte que
     * comparara el instante contra la fecha civil sin convertirla se comería
     * las últimas cinco horas de cada día — justo las de más caja.
     *
     * `reporteVentas` ya tenía esta prueba; `reporteUtilidad` no tenía ninguna.
     */
    public function test_la_utilidad_de_una_venta_de_las_ultimas_horas_pertenece_a_ese_dia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 23:40:00', config('app.timezone_visualizacion')));

        $this->ingresar('100.000', '2.0000', 'L-001');
        $venta = $this->vender('3.000');

        $this->assertSame('2026-08-21', $venta->fecha->format('Y-m-d'), 'En UTC ya es el día siguiente.');

        $delVeinte = $this->servicio->reporteUtilidad('2026-08-20', '2026-08-20');
        $delVeintiuno = $this->servicio->reporteUtilidad('2026-08-21', '2026-08-21');

        $this->assertSame(0, bccomp($delVeinte['ingreso'], '30.00', 2), 'Para quien vendió fue el día 20.');
        $this->assertSame(0, bccomp($delVeinte['costo'], '6.00', 2));
        $this->assertSame([], $delVeintiuno['por_producto'], 'Y no cuenta también en el 21.');
    }

    public function test_un_periodo_sin_ventas_no_inventa_filas(): void
    {
        $ayer = now()->timezone(config('app.timezone_visualizacion'))->subDay()->format('Y-m-d');

        $reporte = $this->servicio->reporteUtilidad($ayer, $ayer);

        $this->assertSame([], $reporte['por_producto']);
        $this->assertSame(0, bccomp($reporte['ingreso'], '0.00', 2));
        $this->assertSame(0, bccomp($reporte['utilidad'], '0.00', 2));
    }

    // --- Rango de fechas: las dos consultas lo validan igual ---

    /** @return array<string, array{0: string}> */
    public static function reportes(): array
    {
        return ['ventas' => ['reporteVentas'], 'utilidad' => ['reporteUtilidad']];
    }

    #[DataProvider('reportes')]
    public function test_las_dos_fechas_son_obligatorias(string $metodo): void
    {
        $error = $this->rechazoDe($metodo, '', $this->hoy());

        $this->assertSame(CodigoDeError::CAMPO_REQUERIDO, $error->codigo);
        $this->assertSame('desde', $error->detalle['campo'] ?? null);
    }

    #[DataProvider('reportes')]
    public function test_una_fecha_que_no_existe_se_rechaza(string $metodo): void
    {
        foreach (['ayer', '20-08-2026', '2026-02-30'] as $mala) {
            $error = $this->rechazoDe($metodo, $this->hoy(), $mala);

            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo, "«{$mala}» no es una fecha.");
            $this->assertSame('hasta', $error->detalle['campo'] ?? null);
        }
    }

    #[DataProvider('reportes')]
    public function test_la_fecha_inicial_no_puede_ser_posterior_a_la_final(string $metodo): void
    {
        $error = $this->rechazoDe($metodo, '2026-08-20', '2026-08-19');

        $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
        $this->assertSame('desde', $error->detalle['campo'] ?? null);
    }

    /**
     * El tope se prueba **en su borde y derivado de la constante**, no con un
     * rango absurdo: dos años y medio siguen siendo demasiado con cualquier
     * tope, así que una prueba así queda verde aunque alguien mueva el límite.
     * Ese era el hueco que dejaba cambiar uno de los dos topes a 400 sin que
     * nada fallara.
     */
    #[DataProvider('reportes')]
    public function test_el_rango_maximo_entra_y_un_dia_mas_se_rechaza(string $metodo): void
    {
        $desde = '2026-01-01';
        $ultimo = Carbon::parse($desde)->addDays(RangoDeFechas::MAXIMO_DIAS - 1)->format('Y-m-d');
        $unoMas = Carbon::parse($desde)->addDays(RangoDeFechas::MAXIMO_DIAS)->format('Y-m-d');

        $reporte = $this->servicio->{$metodo}($desde, $ultimo);

        $this->assertSame(
            $ultimo,
            $reporte['hasta'],
            'De '.$desde." a {$ultimo} son ".RangoDeFechas::MAXIMO_DIAS.' días contando los dos extremos: entran.'
        );

        $error = $this->rechazoDe($metodo, $desde, $unoMas);

        $this->assertSame(CodigoDeError::CAMPO_FUERA_DE_RANGO, $error->codigo);
        $this->assertSame('hasta', $error->detalle['campo'] ?? null);
    }

    private function rechazoDe(string $metodo, string $desde, string $hasta): ErrorDeDominio
    {
        try {
            $this->servicio->{$metodo}($desde, $hasta);
        } catch (ErrorDeDominio $error) {
            return $error;
        }

        $this->fail("{$metodo}({$desde}, {$hasta}) tendría que haber sido rechazado.");
    }

    /** @param  array<string, mixed>  $reporte */
    private function assertDesgloseSumaElTotal(array $reporte): void
    {
        foreach (['por_comprobante', 'por_metodo_pago'] as $desglose) {
            $suma = '0.00';
            $cantidad = 0;

            foreach ($reporte[$desglose] as $fila) {
                $suma = bcadd($suma, $fila['total'], 2);
                $cantidad += $fila['cantidad'];
            }

            $this->assertSame(0, bccomp($suma, $reporte['total'], 2), "El desglose {$desglose} no suma el total.");
            $this->assertSame($reporte['cantidad'], $cantidad, "El desglose {$desglose} no cuenta todas las ventas.");
        }
    }
}
