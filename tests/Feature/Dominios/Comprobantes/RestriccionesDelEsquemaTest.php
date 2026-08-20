<?php

namespace Tests\Feature\Dominios\Comprobantes;

use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Las restricciones que sostienen la numeración y los importes viven en la
 * base, no solo en el servicio: son datos tributarios, y un correlativo
 * repetido o un total que no cuadra no se arreglan después. Cualquier camino
 * que escriba —una migración futura, un seeder, una consulta a mano— topa con
 * ellas igual (MIG-007, MIG-008).
 */
final class RestriccionesDelEsquemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_dos_comprobantes_con_la_misma_serie_y_correlativo_se_rechazan(): void
    {
        $this->insertarComprobante(1);

        $this->expectException(QueryException::class);

        $this->insertarComprobante(1);
    }

    public function test_el_mismo_correlativo_en_otra_serie_si_se_admite(): void
    {
        $this->insertarComprobante(1);

        $this->insertarComprobante(1, serie: 'B002');

        $this->assertSame(2, DB::table('comprobantes')->count());
    }

    /** El total tiene que ser exactamente la base más el IGV, lo escriba quien lo escriba. */
    public function test_una_venta_cuyo_total_no_cuadra_se_rechaza(): void
    {
        $this->crearVenta();

        $this->expectException(QueryException::class);

        $this->crearVenta(total: '999.00');
    }

    private function insertarComprobante(int $correlativo, string $serie = 'B001'): void
    {
        DB::table('comprobantes')->insert([
            'venta_id' => $this->crearVenta(),
            'serie_comprobante_id' => $this->idDeSerie($serie),
            'tipo_comprobante' => '03',
            'serie' => $serie,
            'correlativo' => $correlativo,
            'fecha_emision' => now()->format('Y-m-d'),
            'estado' => 'PENDIENTE',
            'intentos' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function idDeSerie(string $serie): int
    {
        $existente = DB::table('series_comprobante')->where('serie', $serie)->value('id');

        return $existente ?? DB::table('series_comprobante')->insertGetId([
            'tipo_comprobante' => '03',
            'serie' => $serie,
            'correlativo_actual' => 0,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearVenta(string $subtotal = '100.00', string $igv = '18.00', string $total = '118.00'): int
    {
        return DB::table('ventas')->insertGetId([
            'cliente_id' => Cliente::factory()->create()->id,
            'usuario_id' => Usuario::factory()->create()->id,
            'fecha' => now(),
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'metodo_pago' => 'efectivo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
