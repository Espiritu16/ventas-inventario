<?php

namespace Tests\Feature\Dominios\Usuarios;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MIG-009 y RNF-004. Una bitácora existe justamente para los casos en que
 * alguien querría borrar su rastro, así que no alcanza con que el código no
 * la modifique: la base tiene que negarse.
 */
final class AuditoriaAppendOnlyTest extends TestCase
{
    use RefreshDatabase;

    public function test_las_tablas_existen_con_sus_columnas(): void
    {
        $this->assertTrue(Schema::hasColumns('auditorias', [
            'id', 'entidad', 'entidad_id', 'accion', 'usuario_id', 'origen',
            'fecha', 'valores_anteriores', 'valores_nuevos',
        ]));

        $this->assertTrue(Schema::hasColumns('log_errores', [
            'id', 'mensaje', 'stack_trace', 'contexto', 'severidad', 'fecha', 'resuelto', 'trace_id',
        ]));
    }

    public function test_la_aplicacion_puede_insertar_y_leer_auditoria(): void
    {
        DB::table('auditorias')->insert($this->fila());

        $this->assertSame(1, DB::table('auditorias')->count());
    }

    public function test_la_base_impide_actualizar_una_fila_de_auditoria(): void
    {
        DB::table('auditorias')->insert($this->fila());

        $this->assertFallaPorPrivilegios(
            fn () => DB::table('auditorias')->where('entidad', 'Usuario')->update(['accion' => 'eliminar']),
            'La aplicación pudo modificar una fila de auditoría.'
        );

        $this->assertSame('crear', DB::table('auditorias')->value('accion'));
    }

    public function test_la_base_impide_borrar_una_fila_de_auditoria(): void
    {
        DB::table('auditorias')->insert($this->fila());

        $this->assertFallaPorPrivilegios(
            fn () => DB::table('auditorias')->where('entidad', 'Usuario')->delete(),
            'La aplicación pudo borrar una fila de auditoría.'
        );

        $this->assertSame(1, DB::table('auditorias')->count());
    }

    public function test_la_base_rechaza_una_accion_fuera_de_las_tres_permitidas(): void
    {
        $this->expectException(QueryException::class);

        DB::table('auditorias')->insert(['accion' => 'inventada'] + $this->fila());
    }

    /**
     * Ejecuta algo que debe fallar por privilegios y deja la transacción
     * utilizable después.
     *
     * PostgreSQL aborta la transacción entera ante un error, así que sin un
     * savepoint la comprobación siguiente —que la fila sigue intacta— no
     * podría ni ejecutarse.
     */
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

    /** @return array<string, mixed> */
    private function fila(): array
    {
        return [
            'entidad' => 'Usuario',
            'entidad_id' => 1,
            'accion' => 'crear',
            'usuario_id' => 1,
            'origen' => 'usuario',
            'fecha' => now(),
        ];
    }
}
