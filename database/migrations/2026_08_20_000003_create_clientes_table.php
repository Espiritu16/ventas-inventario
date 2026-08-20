<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-006 — `clientes`.
 *
 * La unicidad es un índice **parcial**: solo aplica cuando hay número de
 * documento. Sin eso, la segunda venta a público general chocaría contra la
 * primera, y las boletas por debajo del tope no exigen identificar a nadie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tipo_documento', 2);
            $tabla->string('numero_documento', 15)->nullable();
            $tabla->string('nombre', 200);
            $tabla->string('direccion', 255)->nullable();
            $tabla->string('telefono', 20)->nullable();
            $tabla->string('email', 150)->nullable();
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();

            $tabla->index('nombre');
        });

        DB::statement("alter table clientes add constraint clientes_tipo_documento_check check (tipo_documento in ('0','1','4','6'))");

        // Sin documento solo es admisible para el tipo 0; con cualquier otro
        // tipo, el número es obligatorio.
        DB::statement(
            "alter table clientes add constraint clientes_documento_segun_tipo_check
             check ((tipo_documento = '0' and numero_documento is null)
                 or (tipo_documento <> '0' and numero_documento is not null))"
        );

        DB::statement(
            'create unique index clientes_documento_unico
             on clientes (tipo_documento, numero_documento)
             where numero_documento is not null'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
