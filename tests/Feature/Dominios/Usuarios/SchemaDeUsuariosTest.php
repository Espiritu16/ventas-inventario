<?php

namespace Tests\Feature\Dominios\Usuarios;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * MIG-001. Lo que se comprueba no es que la validación rechace un rol
 * inventado —eso ya lo cubre el servicio—, sino que la base lo rechace
 * también: una fila escrita por fuera de la aplicación tampoco puede tener un
 * rol que no existe.
 */
final class SchemaDeUsuariosTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_tabla_existe_con_sus_columnas(): void
    {
        $this->assertTrue(Schema::hasTable('usuarios'));
        $this->assertTrue(Schema::hasColumns('usuarios', [
            'id', 'nombre', 'email', 'password', 'rol', 'activo', 'created_at', 'updated_at',
        ]));
    }

    public function test_la_base_rechaza_un_rol_fuera_de_los_dos_permitidos(): void
    {
        $this->expectException(QueryException::class);

        DB::table('usuarios')->insert([
            'nombre' => 'Quien Sea',
            'email' => 'quien@ejemplo.pe',
            'password' => 'hash',
            'rol' => 'supervisor',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_la_base_rechaza_un_correo_repetido(): void
    {
        $fila = [
            'nombre' => 'Ana Quispe',
            'email' => 'ana@ejemplo.pe',
            'password' => 'hash',
            'rol' => 'vendedor',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('usuarios')->insert($fila);

        $this->expectException(QueryException::class);

        DB::table('usuarios')->insert($fila);
    }
}
