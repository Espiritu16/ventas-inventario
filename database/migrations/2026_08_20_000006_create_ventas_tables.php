<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-007 — `ventas`, `detalle_ventas` y `detalle_venta_lotes`.
 *
 * El CHECK `subtotal + igv = total` sostiene en la base la aritmética del
 * comprobante: si base e IGV no suman exactamente el total, SUNAT rechaza el
 * documento, y descubrirlo al emitir sería descubrirlo después de cobrar.
 *
 * `detalle_venta_lotes` congela el costo del lote al momento de la salida: el
 * reporte de utilidad no puede depender de que el lote siga existiendo con el
 * mismo costo, ni de un promedio (RF-021, ADR-0004).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $tabla->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabla->timestampTz('fecha');
            $tabla->decimal('subtotal', 12, 2);
            $tabla->decimal('igv', 12, 2);
            $tabla->decimal('total', 12, 2);
            $tabla->string('metodo_pago', 15);
            $tabla->timestampsTz();

            $tabla->index('fecha');
            $tabla->index('usuario_id');
        });

        DB::statement('alter table ventas add constraint ventas_subtotal_positivo check (subtotal > 0)');
        DB::statement('alter table ventas add constraint ventas_igv_no_negativo check (igv >= 0)');
        DB::statement('alter table ventas add constraint ventas_total_positivo check (total > 0)');
        DB::statement('alter table ventas add constraint ventas_suma_exacta check (subtotal + igv = total)');
        DB::statement("alter table ventas add constraint ventas_metodo_pago_check check (metodo_pago in ('efectivo','tarjeta','billetera'))");

        Schema::create('detalle_ventas', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('venta_id')->constrained('ventas')->restrictOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->decimal('cantidad', 12, 3);
            $tabla->string('tipo_precio', 6);
            $tabla->decimal('precio_unitario', 12, 4);
            $tabla->decimal('importe', 12, 2);
            $tabla->timestampsTz();

            $tabla->index('venta_id');
            $tabla->index('producto_id');
        });

        DB::statement('alter table detalle_ventas add constraint detalle_ventas_cantidad_positiva check (cantidad > 0)');
        DB::statement('alter table detalle_ventas add constraint detalle_ventas_precio_positivo check (precio_unitario > 0)');
        DB::statement('alter table detalle_ventas add constraint detalle_ventas_importe_positivo check (importe > 0)');
        DB::statement("alter table detalle_ventas add constraint detalle_ventas_tipo_precio_check check (tipo_precio in ('menor','mayor'))");

        Schema::create('detalle_venta_lotes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('detalle_venta_id')->constrained('detalle_ventas')->restrictOnDelete();
            $tabla->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $tabla->decimal('cantidad', 12, 3);
            $tabla->decimal('costo_unitario', 12, 4);

            $tabla->index('detalle_venta_id');
            $tabla->index('lote_id');
        });

        DB::statement('alter table detalle_venta_lotes add constraint detalle_venta_lotes_cantidad_positiva check (cantidad > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta_lotes');
        Schema::dropIfExists('detalle_ventas');
        Schema::dropIfExists('ventas');
    }
};
