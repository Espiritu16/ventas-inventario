<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-005 — `movimientos_inventario`, el kardex.
 *
 * Append-only por privilegios de la base, igual que `auditorias`: la
 * aplicación solo puede insertar y leer. Una corrección se registra como un
 * ajuste nuevo, nunca modificando el historial (RNF-004, ADR-0004). Por eso no
 * lleva `updated_at`: no hay actualización posible que registrar.
 *
 * `origen_id` no lleva clave foránea porque apunta a tablas distintas según
 * `origen_tipo` — una compra, una venta o un ajuste.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('lote_id')->constrained('lotes')->restrictOnDelete();
            $tabla->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $tabla->string('tipo', 10);
            $tabla->decimal('cantidad', 12, 3);
            $tabla->decimal('costo_unitario', 12, 4);
            $tabla->string('motivo', 30)->nullable();
            $tabla->string('origen_tipo', 20);
            $tabla->unsignedBigInteger('origen_id');
            $tabla->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $tabla->timestampTz('created_at');

            $tabla->index(['producto_id', 'created_at']);
            $tabla->index('lote_id');
            $tabla->index(['origen_tipo', 'origen_id']);
        });

        DB::statement("alter table movimientos_inventario add constraint movimientos_tipo_check check (tipo in ('ingreso','salida','ajuste'))");
        DB::statement("alter table movimientos_inventario add constraint movimientos_origen_check check (origen_tipo in ('compra','venta','ajuste'))");

        // Un movimiento de cantidad cero no registra nada y ensucia el kardex.
        DB::statement('alter table movimientos_inventario add constraint movimientos_cantidad_no_cero check (cantidad <> 0)');

        // Un ajuste sin motivo no se puede auditar: es la regla de RF-009
        // sostenida por la base, no solo por la validación.
        DB::statement(
            "alter table movimientos_inventario add constraint movimientos_ajuste_con_motivo
             check (tipo <> 'ajuste' or motivo is not null)"
        );

        $rolDeLaAplicacion = DB::scalar('select quote_ident(current_user)');
        DB::statement("revoke update, delete on movimientos_inventario from {$rolDeLaAplicacion}");
    }

    public function down(): void
    {
        $rolDeLaAplicacion = DB::scalar('select quote_ident(current_user)');
        DB::statement("grant update, delete on movimientos_inventario to {$rolDeLaAplicacion}");

        Schema::dropIfExists('movimientos_inventario');
    }
};
