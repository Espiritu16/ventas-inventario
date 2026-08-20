<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-002 — `categorias` y `productos`.
 *
 * La regla de precios de RF-004 vive en la base y no solo en la validación:
 * un producto cuyo precio al por mayor supere al precio al por menor haría
 * perder dinero en cada venta mayorista, y eso no puede depender de que la
 * fila haya entrado por la aplicación.
 *
 * Sin existencias: el stock lo crean los lotes de la compra, en S-04-B.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre', 80)->unique();
            $tabla->string('descripcion', 255)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();
        });

        Schema::create('productos', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('codigo', 40)->unique();
            $tabla->string('nombre', 150);
            $tabla->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $tabla->string('unidad_medida', 10)->default('NIU');
            $tabla->decimal('precio_menor', 12, 4);
            $tabla->decimal('precio_mayor', 12, 4);
            $tabla->decimal('stock_minimo', 12, 3)->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();

            $tabla->index('categoria_id');
            $tabla->index('nombre');
        });

        DB::statement('alter table productos add constraint productos_precio_menor_positivo check (precio_menor > 0)');
        DB::statement('alter table productos add constraint productos_precio_mayor_positivo check (precio_mayor > 0)');
        DB::statement('alter table productos add constraint productos_stock_minimo_no_negativo check (stock_minimo >= 0)');

        // RF-004: el precio al por mayor nunca supera al de por menor.
        DB::statement('alter table productos add constraint productos_precio_mayor_valido check (precio_mayor <= precio_menor)');
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
        Schema::dropIfExists('categorias');
    }
};
