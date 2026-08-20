<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-008 — `series_comprobante`, `resumenes_diarios` y `comprobantes`.
 *
 * La unicidad de `(tipo, serie, correlativo)` es la garantía tributaria: dos
 * comprobantes con el mismo número serían dos documentos distintos con la
 * misma identidad ante SUNAT. La reserva del correlativo se serializa con
 * bloqueo sobre la fila de la serie, y esta restricción es la red por si algo
 * lo saltea.
 *
 * `fecha_emision` es una **fecha civil en zona de Lima**: es un dato
 * tributario y una venta cerrada a las 23:40 no puede quedar emitida con la
 * fecha del día siguiente. El resumen diario de boletas se agrupa por este
 * campo, así que un corrimiento partiría un día en dos resúmenes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_comprobante', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('tipo_comprobante', 2);
            $tabla->string('serie', 4);
            $tabla->integer('correlativo_actual')->default(0);
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();

            $tabla->unique(['tipo_comprobante', 'serie']);
        });

        DB::statement("alter table series_comprobante add constraint series_tipo_check check (tipo_comprobante in ('01','03'))");
        DB::statement('alter table series_comprobante add constraint series_correlativo_no_negativo check (correlativo_actual >= 0)');

        Schema::create('resumenes_diarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->date('fecha_referencia');
            $tabla->integer('correlativo');
            $tabla->string('estado', 12)->default('PENDIENTE');
            $tabla->string('ticket', 50)->nullable();
            $tabla->string('ruta_xml', 255)->nullable();
            $tabla->string('ruta_cdr', 255)->nullable();
            $tabla->string('codigo_sunat', 10)->nullable();
            $tabla->string('mensaje_sunat', 500)->nullable();
            $tabla->timestampTz('enviado_en')->nullable();
            $tabla->timestampsTz();

            $tabla->unique(['fecha_referencia', 'correlativo']);
            $tabla->index('estado');
        });

        DB::statement("alter table resumenes_diarios add constraint resumenes_estado_check check (estado in ('PENDIENTE','ENVIADO','ACEPTADO','RECHAZADO'))");

        Schema::create('comprobantes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->foreignId('venta_id')->unique()->constrained('ventas')->restrictOnDelete();
            $tabla->foreignId('serie_comprobante_id')->constrained('series_comprobante')->restrictOnDelete();
            $tabla->string('tipo_comprobante', 2);
            $tabla->string('serie', 4);
            $tabla->integer('correlativo');
            $tabla->date('fecha_emision');
            $tabla->string('estado', 12)->default('PENDIENTE');
            $tabla->integer('intentos')->default(0);
            $tabla->timestampTz('enviado_en')->nullable();
            $tabla->timestampTz('respondido_en')->nullable();
            $tabla->string('hash_xml', 100)->nullable();
            $tabla->string('ruta_xml', 255)->nullable();
            $tabla->string('ruta_cdr', 255)->nullable();
            $tabla->string('codigo_sunat', 10)->nullable();
            $tabla->string('mensaje_sunat', 500)->nullable();
            $tabla->foreignId('resumen_diario_id')->nullable()->constrained('resumenes_diarios')->restrictOnDelete();
            $tabla->timestampsTz();

            $tabla->unique(['tipo_comprobante', 'serie', 'correlativo']);
            $tabla->index('estado');
            $tabla->index('fecha_emision');
        });

        DB::statement("alter table comprobantes add constraint comprobantes_tipo_check check (tipo_comprobante in ('01','03'))");
        DB::statement("alter table comprobantes add constraint comprobantes_estado_check check (estado in ('PENDIENTE','ENVIADO','ACEPTADO','RECHAZADO'))");
        DB::statement('alter table comprobantes add constraint comprobantes_correlativo_positivo check (correlativo > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
        Schema::dropIfExists('resumenes_diarios');
        Schema::dropIfExists('series_comprobante');
    }
};
