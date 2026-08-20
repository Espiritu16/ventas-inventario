<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-009 — `auditorias` y `log_errores` (RNF-004).
 *
 * `auditorias` es append-only, y eso lo garantiza la base: a la aplicación se
 * le revocan UPDATE y DELETE, así que un registro no se puede alterar ni
 * borrar aunque el código lo intente. La convención sola no alcanza para una
 * bitácora que existe justamente para cuando alguien quiere borrar su rastro.
 *
 * `usuario_id` no lleva clave foránea a propósito: la fila debe sobrevivir
 * intacta a cualquier cambio en `usuarios`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('entidad', 50);
            $tabla->unsignedBigInteger('entidad_id');
            $tabla->string('accion', 12);
            $tabla->unsignedBigInteger('usuario_id')->nullable();
            $tabla->string('origen', 40)->default('usuario');
            $tabla->timestampTz('fecha');
            $tabla->jsonb('valores_anteriores')->nullable();
            $tabla->jsonb('valores_nuevos')->nullable();

            $tabla->index(['entidad', 'entidad_id']);
            $tabla->index('fecha');
        });

        DB::statement(
            "alter table auditorias add constraint auditorias_accion_check
             check (accion in ('crear', 'actualizar', 'eliminar'))"
        );

        Schema::create('log_errores', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->text('mensaje');
            $tabla->text('stack_trace')->nullable();
            $tabla->jsonb('contexto')->nullable();
            $tabla->string('severidad', 10);
            $tabla->timestampTz('fecha');
            $tabla->boolean('resuelto')->default(false);
            $tabla->string('trace_id', 64);

            $tabla->index('fecha');
            $tabla->index('severidad');
            $tabla->index('trace_id');
        });

        DB::statement(
            "alter table log_errores add constraint log_errores_severidad_check
             check (severidad in ('info', 'warning', 'error', 'critical'))"
        );

        // El identificador lo cita el propio motor: no hay input de usuario
        // en esta sentencia, y REVOKE no admite parámetros ligados.
        $rolDeLaAplicacion = DB::scalar('select quote_ident(current_user)');
        DB::statement("revoke update, delete on auditorias from {$rolDeLaAplicacion}");
    }

    public function down(): void
    {
        $rolDeLaAplicacion = DB::scalar('select quote_ident(current_user)');
        DB::statement("grant update, delete on auditorias to {$rolDeLaAplicacion}");

        Schema::dropIfExists('log_errores');
        Schema::dropIfExists('auditorias');
    }
};
