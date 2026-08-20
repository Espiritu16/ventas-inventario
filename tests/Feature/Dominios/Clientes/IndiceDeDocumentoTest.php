<?php

namespace Tests\Feature\Dominios\Clientes;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El modelo aprobado exige que la unicidad de documento sea un índice
 * **parcial**, restringido a las filas que tienen número.
 *
 * Esta comprobación es estructural y no de comportamiento, a propósito: el
 * comportamiento no la distingue. PostgreSQL trata dos NULL como distintos en
 * cualquier índice único, así que varios clientes sin documento conviven con
 * el índice parcial y sin él —verificado en PostgreSQL 18.3—, y una prueba de
 * comportamiento pasaría igual aunque alguien quitara la condición.
 *
 * Lo que la parcialidad sí aporta: no indexa las filas sin documento, y deja
 * la garantía en la definición del índice en vez de depender de cómo el motor
 * trate los nulos, que es configurable desde PostgreSQL 15 con
 * `NULLS NOT DISTINCT`.
 */
final class IndiceDeDocumentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_unicidad_de_documento_es_un_indice_parcial(): void
    {
        $definicion = (string) DB::scalar(
            'select indexdef from pg_indexes where tablename = ? and indexname = ?',
            ['clientes', 'clientes_documento_unico']
        );

        $this->assertNotSame('', $definicion, 'Falta el índice de unicidad de documento.');
        $this->assertStringContainsString('UNIQUE', $definicion);
        $this->assertStringContainsString(
            'WHERE (numero_documento IS NOT NULL)',
            $definicion,
            'El índice debe ser parcial: sin la condición, la garantía queda a merced de cómo el motor trate los nulos.'
        );
    }

    public function test_la_base_rechaza_dos_documentos_iguales_sin_pasar_por_el_servicio(): void
    {
        $fila = [
            'tipo_documento' => '1',
            'numero_documento' => '12345678',
            'nombre' => 'Ana Quispe',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('clientes')->insert($fila);

        $this->expectException(QueryException::class);

        DB::table('clientes')->insert(array_merge($fila, ['nombre' => 'Otra Ana']));
    }
}
