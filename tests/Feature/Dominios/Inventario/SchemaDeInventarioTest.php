<?php

namespace Tests\Feature\Dominios\Inventario;

use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MIG-004 y MIG-005. Lo que la base sostiene por sí misma, sin depender de que
 * la escritura haya pasado por el servicio.
 */
final class SchemaDeInventarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_base_impide_dejar_un_lote_en_negativo(): void
    {
        $lote = Lote::factory()->create();

        try {
            DB::table('lotes')->where('id', $lote->id)->update(['cantidad_actual' => '-0.001']);
            $this->fail('La base aceptó un lote con cantidad negativa.');
        } catch (QueryException $error) {
            $this->assertSame('23514', $error->getCode());
            $this->assertStringContainsString('lotes_cantidad_no_negativa', $error->getMessage());
        }
    }

    public function test_la_base_impide_modificar_el_kardex(): void
    {
        $this->insertarMovimiento();

        $this->assertFallaPorPrivilegios(
            fn () => DB::table('movimientos_inventario')->where('tipo', 'ingreso')->update(['cantidad' => '999.000']),
            'Se pudo modificar una línea del kardex.'
        );
    }

    public function test_la_base_impide_borrar_el_kardex(): void
    {
        $this->insertarMovimiento();

        $this->assertFallaPorPrivilegios(
            fn () => DB::table('movimientos_inventario')->where('tipo', 'ingreso')->delete(),
            'Se pudo borrar una línea del kardex.'
        );

        $this->assertSame(1, DB::table('movimientos_inventario')->count());
    }

    public function test_la_base_exige_motivo_en_un_ajuste(): void
    {
        $this->expectException(QueryException::class);

        $this->insertarMovimiento(['tipo' => 'ajuste', 'origen_tipo' => 'ajuste', 'motivo' => null]);
    }

    public function test_la_base_rechaza_un_movimiento_de_cantidad_cero(): void
    {
        $this->expectException(QueryException::class);

        $this->insertarMovimiento(['cantidad' => '0.000']);
    }

    public function test_dos_ingresos_iguales_no_pueden_abrir_dos_lotes(): void
    {
        $lote = Lote::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('lotes')->insert([
            'producto_id' => $lote->producto_id,
            'codigo_lote' => $lote->codigo_lote,
            'fecha_vencimiento' => $lote->fecha_vencimiento->format('Y-m-d'),
            'costo_unitario' => $lote->costo_unitario,
            'cantidad_actual' => '1.000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- La fecha de vencimiento es una fecha civil (nota heredada de S-00) ---

    public static function formasDeLaMismaFecha(): array
    {
        return [
            'cadena AAAA-MM-DD' => ['2026-08-30'],
            'medianoche en Lima' => ['2026-08-30 00:00:00'],
            'media tarde en Lima' => ['2026-08-30 15:30:00'],
            'un minuto antes de medianoche' => ['2026-08-30 23:59:59'],
        ];
    }

    /**
     * El vencimiento es la fecha impresa en el envase: no es un instante y no
     * se convierte de zona. Un lote que vence el 30 vence el 30 mirado desde
     * donde sea.
     *
     * Importa porque decide el orden FEFO: correrlo un día cambia qué lote
     * sale primero, y el error sería invisible — el stock cuadra igual.
     */
    #[DataProvider('formasDeLaMismaFecha')]
    public function test_el_vencimiento_no_corre_de_dia(string $entrada): void
    {
        $valor = str_contains($entrada, ':')
            ? Carbon::parse($entrada, config('app.timezone_visualizacion'))
            : $entrada;

        $lote = Lote::factory()->create(['fecha_vencimiento' => $valor]);

        $this->assertSame('2026-08-30', $lote->refresh()->fecha_vencimiento->format('Y-m-d'));
        $this->assertSame('2026-08-30', DB::table('lotes')->where('id', $lote->id)->value('fecha_vencimiento'));
    }

    /** @param  array<string, mixed>  $cambios */
    private function insertarMovimiento(array $cambios = []): void
    {
        $lote = Lote::factory()->create();

        DB::table('movimientos_inventario')->insert(array_merge([
            'lote_id' => $lote->id,
            'producto_id' => $lote->producto_id,
            'tipo' => 'ingreso',
            'cantidad' => '10.000',
            'costo_unitario' => '5.0000',
            'motivo' => null,
            'origen_tipo' => 'compra',
            'origen_id' => 1,
            'usuario_id' => Usuario::factory()->create()->id,
            'created_at' => now(),
        ], $cambios));
    }

    private function assertFallaPorPrivilegios(callable $operacion, string $siNoFalla): void
    {
        DB::beginTransaction();

        try {
            $operacion();
            DB::rollBack();
            $this->fail($siNoFalla);
        } catch (QueryException $error) {
            DB::rollBack();
            $this->assertSame('42501', $error->getCode(), 'Debe fallar por privilegios, no por otra causa.');
        }
    }
}
