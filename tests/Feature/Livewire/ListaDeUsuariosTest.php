<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Usuarios\Livewire\ListaDeUsuarios;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-03. Pantalla de usuarios: listado, alta y edición, con sus cuatro
 * estados y el rechazo del servidor a la autodesactivación.
 */
final class ListaDeUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdministrador(): Usuario
    {
        $admin = Usuario::factory()->administrador()->create(['nombre' => 'Ada Admin']);

        $this->actingAs($admin);

        return $admin;
    }

    // --- La pantalla responde ---

    public function test_la_pantalla_se_sirve_al_administrador_con_su_listado(): void
    {
        $this->comoAdministrador();
        Usuario::factory()->create(['nombre' => 'Ana Quispe']);

        $this->get('/usuarios')
            ->assertOk()
            ->assertSee('Ana Quispe')
            ->assertSee('Nuevo usuario');
    }

    // --- Cuatro estados ---

    public function test_estado_vacio_cuando_la_busqueda_no_encuentra_nada(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->set('buscar', 'no-existe-nadie-asi')
            ->assertSee('No hay usuarios que coincidan')
            ->assertDontSee('fila-usuario', false);
    }

    public function test_estado_con_datos_lista_a_los_usuarios(): void
    {
        $this->comoAdministrador();
        Usuario::factory()->create(['nombre' => 'Ana Quispe']);

        Livewire::test(ListaDeUsuarios::class)
            ->assertSee('Ana Quispe')
            ->assertSee('Ada Admin');
    }

    public function test_estado_de_error_se_muestra_como_aviso_de_la_operacion(): void
    {
        $admin = $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->call('cambiarEstado', $admin->id, false)
            ->assertSet('exito', null)
            ->assertSee('no puede quitarse su propio rol ni desactivarse');
    }

    public function test_estado_de_exito_tras_crear(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->call('nuevo')
            ->set('nombre', 'Ana Quispe')
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->set('rol', Usuario::ROL_VENDEDOR)
            ->call('guardar')
            ->assertSet('error', null)
            ->assertSee('Usuario creado');

        $this->assertDatabaseHas('usuarios', ['email' => 'ana@ejemplo.pe']);
    }

    // --- Alta y edición ---

    public function test_la_edicion_carga_los_datos_del_usuario(): void
    {
        $this->comoAdministrador();
        $usuario = Usuario::factory()->create(['nombre' => 'Ana Quispe', 'email' => 'ana@ejemplo.pe']);

        Livewire::test(ListaDeUsuarios::class)
            ->call('editar', $usuario->id)
            ->assertSet('editando', $usuario->id)
            ->assertSet('nombre', 'Ana Quispe')
            ->assertSet('email', 'ana@ejemplo.pe')
            ->assertSet('formularioAbierto', true);
    }

    public function test_la_edicion_guarda_el_cambio_de_rol(): void
    {
        $this->comoAdministrador();
        $usuario = Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]);

        Livewire::test(ListaDeUsuarios::class)
            ->call('editar', $usuario->id)
            ->set('rol', Usuario::ROL_ADMINISTRADOR)
            ->call('guardar')
            ->assertSee('Usuario actualizado');

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $usuario->refresh()->rol);
    }

    /** El correo repetido se señala junto a su campo, no como aviso suelto. */
    public function test_el_correo_repetido_se_muestra_junto_al_campo(): void
    {
        $this->comoAdministrador();
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe']);

        Livewire::test(ListaDeUsuarios::class)
            ->call('nuevo')
            ->set('nombre', 'Otra Ana')
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->call('guardar')
            ->assertSet('campoConError', 'email')
            ->assertSet('error', null)
            ->assertSeeHtml('data-prueba="error-de-campo"');
    }

    /**
     * La validación es del servicio, no de la pantalla: si la pantalla
     * decidiera por su cuenta, las dos reglas podrían divergir en silencio.
     */
    public function test_el_dato_invalido_lo_rechaza_el_servicio(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->call('nuevo')
            ->set('nombre', 'Ana Quispe')
            ->set('email', 'no-es-un-correo')
            ->set('password', 'contrasena-valida')
            ->call('guardar')
            ->assertSet('campoConError', 'email');

        $this->assertDatabaseMissing('usuarios', ['nombre' => 'Ana Quispe']);
    }

    // --- Autodesactivación: la muestra el servidor, no la esconde la pantalla ---

    /**
     * El botón de desactivar se ofrece también sobre la propia fila: el
     * rechazo tiene que venir del servidor y verse. Si la única señal fuera
     * que el botón no está, el método se podría invocar igual y nada lo
     * impediría.
     */
    public function test_el_boton_de_desactivarse_se_ofrece_y_el_servidor_lo_rechaza(): void
    {
        $admin = $this->comoAdministrador();

        $componente = Livewire::test(ListaDeUsuarios::class);

        $componente->assertSeeHtml('wire:click="cambiarEstado('.$admin->id.', false)"');

        $componente->call('cambiarEstado', $admin->id, false)
            ->assertSee('no puede quitarse su propio rol ni desactivarse');

        $this->assertTrue($admin->refresh()->activo);
    }

    public function test_no_puede_quitarse_su_propio_rol_de_administrador(): void
    {
        $admin = $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->call('editar', $admin->id)
            ->set('rol', Usuario::ROL_VENDEDOR)
            ->call('guardar')
            ->assertSee('no puede quitarse su propio rol ni desactivarse');

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $admin->refresh()->rol);
    }

    public function test_si_puede_desactivar_a_otro(): void
    {
        $this->comoAdministrador();
        $otro = Usuario::factory()->create();

        Livewire::test(ListaDeUsuarios::class)
            ->call('cambiarEstado', $otro->id, false)
            ->assertSee('Usuario desactivado');

        $this->assertFalse($otro->refresh()->activo);
    }

    // --- Estado en la URL ---

    public function test_la_busqueda_y_la_pagina_viven_en_la_url(): void
    {
        $this->comoAdministrador();

        Livewire::withQueryParams(['buscar' => 'ana', 'pagina' => 2])
            ->test(ListaDeUsuarios::class)
            ->assertSet('buscar', 'ana')
            ->assertSet('pagina', 2);
    }

    public function test_cambiar_la_busqueda_vuelve_a_la_primera_pagina(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeUsuarios::class)
            ->set('pagina', 3)
            ->set('buscar', 'ana')
            ->assertSet('pagina', 1);
    }

    public function test_la_busqueda_filtra_por_nombre_y_por_correo(): void
    {
        $this->comoAdministrador();
        Usuario::factory()->create(['nombre' => 'Ana Quispe', 'email' => 'ana@ejemplo.pe']);
        Usuario::factory()->create(['nombre' => 'Beto Ríos', 'email' => 'beto@ejemplo.pe']);

        Livewire::test(ListaDeUsuarios::class)
            ->set('buscar', 'beto@')
            ->assertSee('Beto Ríos')
            ->assertDontSee('Ana Quispe');
    }

    /** El listado nunca expone el hash de contraseña (RNF-014). */
    public function test_el_listado_no_expone_contrasenas(): void
    {
        $this->comoAdministrador();
        Usuario::factory()->create(['password' => 'contrasena-valida']);

        $html = $this->get('/usuarios')->getContent();

        $this->assertStringNotContainsString('$2y$', $html);
        $this->assertStringNotContainsString('password"', $html);
    }
}
