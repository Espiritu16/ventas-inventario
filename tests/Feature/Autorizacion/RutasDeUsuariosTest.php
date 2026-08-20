<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Las dos operaciones de sesión que sí son HTTP: el navegador las ejecuta como
 * envío de formulario contra el servidor.
 *
 * La gestión de usuarios no está acá. Se declaró como endpoints JSON y la
 * enmienda de Arquitectura del 2026-08-19 la corrigió: son pantallas, y la
 * operación la ejecuta `UsuarioService` en el mismo proceso, sin HTTP de por
 * medio. Sus pruebas de servicio siguen cubriendo el comportamiento; lo que se
 * retiró es la superficie que nadie consumía.
 */
final class RutasDeUsuariosTest extends TestCase
{
    use RefreshDatabase;

    // --- POST /login ---

    public function test_inicia_sesion_con_credenciales_validas(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->postJson('/login', ['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida'])
            ->assertOk()
            ->assertJsonPath('redirigir', '/panel');

        $this->assertAuthenticated();
    }

    public function test_rechaza_credenciales_invalidas_sin_iniciar_sesion(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->postJson('/login', ['email' => 'ana@ejemplo.pe', 'password' => 'equivocada'])
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'CREDENCIALES_INVALIDAS');

        $this->assertGuest();
    }

    public function test_un_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        Usuario::factory()->inactivo()->create(['email' => 'baja@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->postJson('/login', ['email' => 'baja@ejemplo.pe', 'password' => 'contrasena-valida'])
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'CREDENCIALES_INVALIDAS');

        $this->assertGuest();
    }

    /** Si el identificador de sesión sobreviviera, una sesión previa quedaría elevada. */
    public function test_la_sesion_se_regenera_al_iniciar(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->get('/');
        $anterior = session()->getId();

        $this->postJson('/login', ['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $this->assertNotSame($anterior, session()->getId());
    }

    // --- POST /logout ---

    public function test_cierra_la_sesion_de_cualquiera_de_los_dos_roles(): void
    {
        foreach ([Usuario::ROL_ADMINISTRADOR, Usuario::ROL_VENDEDOR] as $rol) {
            $this->actingAs(Usuario::factory()->create(['rol' => $rol]))
                ->postJson('/logout')
                ->assertOk();

            $this->assertGuest();
        }
    }

    public function test_cerrar_sesion_sin_tenerla_no_esta_permitido(): void
    {
        $this->postJson('/logout')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }
}
