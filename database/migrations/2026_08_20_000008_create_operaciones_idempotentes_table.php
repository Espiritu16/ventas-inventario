<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Claves de idempotencia de las ventas (RF-011).
 *
 * Un doble clic en la caja registraría dos ventas: descontaría stock real dos
 * veces y consumiría dos correlativos tributarios. Ninguna de las dos cosas se
 * deshace sola.
 *
 * La unicidad de `clave` es lo que serializa el reintento: dos peticiones
 * simultáneas con la misma clave compiten por insertar esta fila, y la que
 * pierde encuentra la venta de la que ganó en vez de crear otra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operaciones_idempotentes', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('clave', 36)->unique();
            // Huella de la petición: la misma clave con datos distintos es un
            // error de quien la envía, no un reintento.
            $tabla->string('huella', 64);
            $tabla->foreignId('venta_id')->nullable()->constrained('ventas')->restrictOnDelete();
            $tabla->timestampTz('expira_en');
            $tabla->timestampsTz();

            $tabla->index('expira_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operaciones_idempotentes');
    }
};
