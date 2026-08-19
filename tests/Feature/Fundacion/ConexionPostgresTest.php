<?php

namespace Tests\Feature\Fundacion;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ADR-0001 y RNF-006: la aplicación habla con PostgreSQL, la conexión trabaja
 * en UTC, y el dinero se guarda con precisión decimal exacta —nunca en punto
 * flotante—.
 *
 * La tabla que usa es de la propia prueba, no de dominio: S-00 no crea
 * entidades de negocio.
 */
final class ConexionPostgresTest extends TestCase
{
    use RefreshDatabase;

    private const TABLA = 'prueba_fundacion';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create(self::TABLA, function (Blueprint $tabla) {
            $tabla->id();
            $tabla->timestampTz('momento');
            $tabla->decimal('importe', 12, 4);
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(self::TABLA);

        parent::tearDown();
    }

    public function test_la_aplicacion_corre_sobre_postgresql(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
    }

    public function test_la_conexion_trabaja_en_utc(): void
    {
        $this->assertSame('UTC', DB::selectOne('show timezone')->TimeZone);
    }

    public function test_un_instante_guardado_se_lee_como_el_mismo_momento_utc(): void
    {
        $momento = Carbon::parse('2026-08-19 23:30:00', 'America/Lima');

        DB::table(self::TABLA)->insert([
            'momento' => $momento,
            'importe' => '0.0000',
        ]);

        $leido = Carbon::parse(DB::table(self::TABLA)->value('momento'));

        $this->assertTrue(
            $momento->equalTo($leido),
            "Se guardó {$momento->toIso8601String()} y se leyó {$leido->toIso8601String()}"
        );
        $this->assertSame('2026-08-20T04:30:00+00:00', $leido->utc()->toIso8601String());
    }

    public function test_los_importes_conservan_su_precision_decimal(): void
    {
        DB::table(self::TABLA)->insert([
            'momento' => Carbon::now(),
            'importe' => '1234.5678',
        ]);

        $importe = DB::table(self::TABLA)->value('importe');

        $this->assertSame('1234.5678', (string) $importe);
    }

    public function test_la_migracion_aplica_y_revierte_desde_base_limpia(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();
        $this->assertTrue(Schema::hasTable('sessions'));

        $this->artisan('migrate:rollback')->assertSuccessful();
        $this->assertFalse(Schema::hasTable('sessions'));
    }
}
