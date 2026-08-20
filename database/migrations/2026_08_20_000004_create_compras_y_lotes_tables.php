<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-004 — `lotes`, `compras` y `detalle_compras`.
 *
 * `fecha_vencimiento` y `fecha_emision` son **fechas civiles**, no instantes:
 * el vencimiento es el que está impreso en el envase y no cambia según desde
 * dónde se mire. Por eso son `date` y no `timestamptz`, y por eso nunca se
 * escriben desde un objeto con hora — ver la nota de `Lote::$casts` y la
 * prueba que lo fija.
 *
 * `cantidad_actual >= 0` es la restricción que impide vender más de lo que
 * hay, sostenida por la base y no solo por el servicio: es la última línea
 * cuando dos operaciones concurren sobre el mismo lote.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('codigo_lote', 40);
            $tabla->date('fecha_vencimiento');
            $tabla->decimal('cantidad_actual', 12, 3);
            $tabla->decimal('costo_unitario', 12, 4);
            $tabla->timestampsTz();

            // Mismo producto, lote, vencimiento y costo se acumulan en una
            // sola fila; un costo distinto abre un lote nuevo, que es lo que
            // permite el costeo real de RF-021.
            $tabla->unique(['producto_id', 'codigo_lote', 'fecha_vencimiento', 'costo_unitario'], 'lotes_identidad_unica');
            $tabla->index(['producto_id', 'fecha_vencimiento']);
            $tabla->index('fecha_vencimiento');
        });

        DB::statement('alter table lotes add constraint lotes_cantidad_no_negativa check (cantidad_actual >= 0)');
        DB::statement('alter table lotes add constraint lotes_costo_positivo check (costo_unitario > 0)');

        Schema::create('compras', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $tabla->string('tipo_documento', 2);
            $tabla->string('serie_documento', 4);
            $tabla->string('numero_documento', 8);
            $tabla->date('fecha_emision');
            $tabla->decimal('total', 12, 2);
            $tabla->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabla->timestampsTz();

            $tabla->unique(['proveedor_id', 'tipo_documento', 'serie_documento', 'numero_documento'], 'compras_documento_unico');
            $tabla->index('fecha_emision');
        });

        DB::statement('alter table compras add constraint compras_total_positivo check (total > 0)');
        DB::statement("alter table compras add constraint compras_tipo_documento_check check (tipo_documento in ('01','03','09','NA'))");

        Schema::create('detalle_compras', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('compra_id')->constrained('compras')->restrictOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->decimal('cantidad', 12, 3);
            $tabla->decimal('costo_unitario', 12, 4);
            $tabla->string('codigo_lote', 40);
            $tabla->date('fecha_vencimiento');
            $tabla->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $tabla->timestampsTz();

            $tabla->index('compra_id');
            $tabla->index('producto_id');
        });

        DB::statement('alter table detalle_compras add constraint detalle_compras_cantidad_positiva check (cantidad > 0)');
        DB::statement('alter table detalle_compras add constraint detalle_compras_costo_positivo check (costo_unitario > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_compras');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('lotes');
    }
};
