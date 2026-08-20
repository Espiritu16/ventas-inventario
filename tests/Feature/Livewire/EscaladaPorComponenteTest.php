<?php

namespace Tests\Feature\Livewire;

use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Usuarios\Livewire\ListaDeUsuarios;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Throwable;

/**
 * Que no se pueda operar la gestión de usuarios sin pasar por la pantalla.
 *
 * El middleware protege la ruta `GET /usuarios`, pero los métodos de un
 * componente no viajan por esa ruta: viajan por el endpoint de actualización
 * de Livewire, que solo exige sesión activa y no distingue rol. Proteger la
 * ruta y dar por protegido el componente es el mismo error que dar por
 * protegido lo que el menú oculta.
 *
 * `UsuarioService` tampoco cubre esto y no debería: es un servicio de dominio
 * y no sabe quién lo llama. Son dos capas distintas, como declara
 * docs/requisitos/actores-permisos.md, no la misma dos veces.
 */
final class EscaladaPorComponenteTest extends TestCase
{
    use RefreshDatabase;

    private function vendedor(): Usuario
    {
        return Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]);
    }

    /**
     * Livewire monta el componente mientras renderiza la vista, así que un
     * rechazo en `mount()` llega envuelto en la excepción de la vista. Se
     * recorre la cadena en vez de exigir el tipo de la envoltura, que es de
     * Laravel y puede cambiar.
     */
    private function rechazoDe(callable $accion): ErrorDeDominio
    {
        try {
            $accion();
        } catch (Throwable $lanzada) {
            for ($e = $lanzada; $e !== null; $e = $e->getPrevious()) {
                if ($e instanceof ErrorDeDominio) {
                    return $e;
                }
            }

            $this->fail('Se rechazó, pero no con un error de la taxonomía: '.$lanzada::class);
        }

        $this->fail('La operación no fue rechazada.');
    }

    /**
     * El caso que motivó esta prueba: sin la comprobación en el componente, un
     * vendedor se daba de alta a sí mismo como administrador sin pasar nunca
     * por la pantalla.
     */
    public function test_un_vendedor_no_puede_crear_usuarios_invocando_el_componente(): void
    {
        $this->actingAs($this->vendedor());

        $rechazo = $this->rechazoDe(fn () => Livewire::test(ListaDeUsuarios::class)
            ->set('nombre', 'Colado Porlivewire')
            ->set('email', 'colado@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->set('rol', Usuario::ROL_ADMINISTRADOR)
            ->call('crear'));

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
        $this->assertDatabaseMissing('usuarios', ['email' => 'colado@ejemplo.pe']);
    }

    public function test_un_vendedor_no_puede_montar_la_pantalla_de_usuarios(): void
    {
        $this->actingAs($this->vendedor());

        $rechazo = $this->rechazoDe(fn () => Livewire::test(ListaDeUsuarios::class));

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }

    public function test_un_vendedor_no_puede_desactivar_a_nadie(): void
    {
        $victima = Usuario::factory()->administrador()->create();

        $this->actingAs($this->vendedor());

        $rechazo = $this->rechazoDe(fn () => Livewire::test(ListaDeUsuarios::class)
            ->call('cambiarEstado', $victima->id, false));

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
        $this->assertTrue($victima->refresh()->activo);
    }

    /** Un administrador desactivado deja de operar, aunque conserve la sesión. */
    public function test_un_administrador_desactivado_no_puede_operar(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin);

        $admin->update(['activo' => false]);

        $rechazo = $this->rechazoDe(fn () => Livewire::test(ListaDeUsuarios::class));

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }

    /** Sin sesión tampoco, aunque el endpoint de Livewire deje pasar al componente. */
    public function test_sin_sesion_no_se_puede_operar(): void
    {
        $rechazo = $this->rechazoDe(fn () => Livewire::test(ListaDeUsuarios::class));

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }

    public function test_el_administrador_si_puede_operar(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());

        Livewire::test(ListaDeUsuarios::class)
            ->set('nombre', 'Ana Quispe')
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->set('rol', Usuario::ROL_VENDEDOR)
            ->call('crear')
            ->assertSee('Usuario creado');

        $this->assertDatabaseHas('usuarios', ['email' => 'ana@ejemplo.pe']);
    }

    // --- Lectura: el permiso se comprueba al servir los datos, no al montar ---

    /**
     * El defecto que QA encontró por HTTP y que este sprint tuvo que corregir.
     *
     * `mount()` corre una sola vez. En cada interacción posterior el componente
     * se hidrata desde el snapshot y `render()` vuelve a consultar sin pasar
     * por el montaje, así que un permiso comprobado solo al montar protege la
     * primera carga y nada más. A un administrador degradado a vendedor con la
     * pantalla abierta, el listado le seguía respondiendo con los nombres y
     * correos de todos hasta que recargara.
     */
    public function test_un_administrador_degradado_deja_de_ver_el_listado(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Usuario::factory()->create(['nombre' => 'Secreto Confidencial']);

        $this->actingAs($admin);

        $componente = Livewire::test(ListaDeUsuarios::class);
        $componente->assertSee('Secreto Confidencial');

        // Se le cambia el rol con la pantalla ya abierta.
        $admin->update(['rol' => Usuario::ROL_VENDEDOR]);

        $rechazo = $this->rechazoDe(fn () => $componente->set('buscar', 'Secreto')->html());

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }

    /**
     * El caso vecino, que parecía el mismo y no lo era: a un usuario
     * desactivado lo frena además el middleware, que comprueba `activo`. Al
     * degradado no lo frenaba nadie. Se cubren los dos por separado para que
     * no vuelvan a confundirse.
     */
    public function test_un_administrador_desactivado_deja_de_ver_el_listado(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Usuario::factory()->create(['nombre' => 'Secreto Confidencial']);

        $this->actingAs($admin);

        $componente = Livewire::test(ListaDeUsuarios::class);
        $componente->assertSee('Secreto Confidencial');

        $admin->update(['activo' => false]);

        $rechazo = $this->rechazoDe(fn () => $componente->set('buscar', 'Secreto')->html());

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }

    /** Cambiar de página tampoco vuelve a servir datos a quien ya no puede verlos. */
    public function test_un_degradado_tampoco_puede_paginar(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        Usuario::factory()->count(3)->create();

        $this->actingAs($admin);

        $componente = Livewire::test(ListaDeUsuarios::class);

        $admin->update(['rol' => Usuario::ROL_VENDEDOR]);

        $rechazo = $this->rechazoDe(fn () => $componente->set('pagina', 2)->html());

        $this->assertSame('NO_AUTORIZADO', $rechazo->codigo->value);
    }
}
