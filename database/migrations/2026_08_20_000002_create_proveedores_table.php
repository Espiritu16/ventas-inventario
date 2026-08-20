<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-003 — `proveedores`.
 *
 * Un proveedor siempre tiene RUC: es quien emite la factura de compra, y sin
 * RUC esa compra no puede sustentarse. El tipo queda restringido por la base
 * para que no entre otro por ninguna vía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tipo_documento', 2)->default('6');
            $tabla->string('numero_documento', 11);
            $tabla->string('razon_social', 200);
            $tabla->string('direccion', 255)->nullable();
            $tabla->string('telefono', 20)->nullable();
            $tabla->string('email', 150)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();

            $tabla->unique(['tipo_documento', 'numero_documento']);
            $tabla->index('razon_social');
        });

        DB::statement("alter table proveedores add constraint proveedores_tipo_documento_check check (tipo_documento = '6')");
        DB::statement("alter table proveedores add constraint proveedores_numero_documento_check check (numero_documento ~ '^[0-9]{11}$')");
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
