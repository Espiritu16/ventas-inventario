<?php

namespace Tests\Feature\Dominios\Catalogo;

use App\Dominios\Catalogo\Modelos\Categoria;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * MIG-002. La regla de precios de RF-004 tiene que sostenerla la base: un
 * producto cuyo precio al por mayor supere al de por menor haría perder dinero
 * en cada venta mayorista, y eso no puede depender de que la fila haya entrado
 * por la aplicación.
 */
final class SchemaDeCatalogoTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tablas_existen_con_sus_columnas(): void
    {
        $this->assertTrue(Schema::hasColumns('categorias', ['id', 'nombre', 'descripcion', 'activo', 'created_at', 'updated_at']));
        $this->assertTrue(Schema::hasColumns('productos', [
            'id', 'codigo', 'nombre', 'categoria_id', 'unidad_medida',
            'precio_menor', 'precio_mayor', 'stock_minimo', 'activo',
        ]));
    }

    public static function filasQueLaBaseDebeRechazar(): array
    {
        return [
            // Cuando una fila viola una sola restricción se nombra cuál, que
            // es lo que se quiere comprobar. Cuando viola varias a la vez,
            // PostgreSQL reporta la primera que evalúa y ese orden no es parte
            // del contrato: fijarlo haría fallar la prueba por un detalle del
            // motor y no por un cambio real.
            'el mayor supera al menor' => [['precio_menor' => '10.0000', 'precio_mayor' => '12.0000'], 'productos_precio_mayor_valido'],
            'precio menor en cero' => [['precio_menor' => '0.0000', 'precio_mayor' => '0.0000'], 'check constraint'],
            'precio menor negativo' => [['precio_menor' => '-1.0000', 'precio_mayor' => '-2.0000'], 'check constraint'],
            'stock mínimo negativo' => [['stock_minimo' => '-1.000'], 'productos_stock_minimo_no_negativo'],
        ];
    }

    /**
     * @param  array<string, mixed>  $cambios
     */
    #[DataProvider('filasQueLaBaseDebeRechazar')]
    public function test_la_base_rechaza_la_fila_invalida(array $cambios, string $esperado): void
    {
        try {
            DB::table('productos')->insert(array_merge($this->fila(), $cambios));
            $this->fail("La base aceptó una fila que debía rechazar por «{$esperado}».");
        } catch (QueryException $error) {
            $this->assertSame('23514', $error->getCode(), 'Debe rechazarla una restricción CHECK.');
            $this->assertStringContainsString($esperado, $error->getMessage());
        }
    }

    public function test_la_base_acepta_precios_iguales(): void
    {
        DB::table('productos')->insert(array_merge($this->fila(), [
            'precio_menor' => '10.0000',
            'precio_mayor' => '10.0000',
        ]));

        $this->assertSame(1, DB::table('productos')->count());
    }

    public function test_la_base_rechaza_un_codigo_repetido(): void
    {
        DB::table('productos')->insert($this->fila());

        $this->expectException(QueryException::class);

        DB::table('productos')->insert($this->fila());
    }

    public function test_la_base_conserva_la_precision_decimal_de_los_precios(): void
    {
        DB::table('productos')->insert(array_merge($this->fila(), [
            'precio_menor' => '1234.5678',
            'precio_mayor' => '1234.5678',
        ]));

        $this->assertSame('1234.5678', (string) DB::table('productos')->value('precio_menor'));
    }

    /** @return array<string, mixed> */
    private function fila(): array
    {
        return [
            'codigo' => 'COD-1',
            'nombre' => 'Producto de prueba',
            'categoria_id' => Categoria::factory()->create()->id,
            'unidad_medida' => 'NIU',
            'precio_menor' => '10.0000',
            'precio_mayor' => '8.0000',
            'stock_minimo' => '0.000',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
