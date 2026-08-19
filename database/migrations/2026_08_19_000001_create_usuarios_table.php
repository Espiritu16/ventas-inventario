<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MIG-001 — tabla `usuarios`.
 *
 * El rol lo restringe la base con un CHECK, no solo la validación: una fila
 * con un rol inventado no debe poder existir aunque alguien escriba en la
 * tabla sin pasar por la aplicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $tabla) {
            $tabla->id();
            $tabla->string('nombre', 120);
            $tabla->string('email', 150)->unique();
            $tabla->string('password', 255); // hash, nunca la contraseña
            $tabla->string('rol', 20);
            $tabla->boolean('activo')->default(true);
            $tabla->timestampsTz();
        });

        DB::statement(
            "alter table usuarios add constraint usuarios_rol_check
             check (rol in ('administrador', 'vendedor'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
