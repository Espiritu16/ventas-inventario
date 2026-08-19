<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Contrato docs/contratos/usuarios.md v1 sobre HTTP, y la matriz de permisos
 * aplicada a cada ruta: sin autenticar, autenticado con permiso y
 * autenticado sin permiso (RNF-013).
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

    // --- GET /usuarios ---

    public function test_el_administrador_lista_usuarios_paginados_sin_contrasenas(): void
    {
        Usuario::factory()->count(3)->create();

        $respuesta = $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson('/usuarios');

        $respuesta->assertOk()->assertJsonPath('por_pagina', 20);
        $this->assertStringNotContainsString('password', $respuesta->getContent());
    }

    public function test_el_vendedor_no_puede_listar_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create())
            ->getJson('/usuarios')
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    public function test_listar_usuarios_sin_sesion_rechaza(): void
    {
        $this->getJson('/usuarios')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    // --- POST /usuarios ---

    public function test_el_administrador_crea_un_usuario(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson('/usuarios', [
                'nombre' => 'Ana Quispe',
                'email' => 'ana@ejemplo.pe',
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ])
            ->assertStatus(201)
            ->assertJsonMissingPath('password');

        $this->assertDatabaseHas('usuarios', ['email' => 'ana@ejemplo.pe']);
    }

    public function test_el_vendedor_no_puede_crear_usuarios(): void
    {
        $this->actingAs(Usuario::factory()->create())
            ->postJson('/usuarios', [
                'nombre' => 'Ana Quispe',
                'email' => 'ana@ejemplo.pe',
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ])
            ->assertStatus(403);

        $this->assertDatabaseMissing('usuarios', ['email' => 'ana@ejemplo.pe']);
    }

    /** RNF-010: el input inválido se rechaza con su código, no se corrige. */
    public function test_el_input_invalido_se_rechaza_con_su_codigo(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson('/usuarios', [
                'nombre' => 'Ana Quispe',
                'email' => 'no-es-un-correo',
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ])
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'CAMPO_FORMATO_INVALIDO');
    }

    public function test_un_correo_repetido_devuelve_conflicto(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe']);

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson('/usuarios', [
                'nombre' => 'Otra Ana',
                'email' => 'ana@ejemplo.pe',
                'password' => 'contrasena-valida',
                'rol' => Usuario::ROL_VENDEDOR,
            ])
            ->assertStatus(409)
            ->assertJsonPath('error.codigo', 'DOCUMENTO_DUPLICADO');
    }

    // --- PATCH /usuarios/{id} ---

    public function test_el_administrador_edita_un_usuario(): void
    {
        $usuario = Usuario::factory()->create(['nombre' => 'Ana Quispe']);

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->patchJson("/usuarios/{$usuario->id}", ['nombre' => 'Ana Quispe Rojas'])
            ->assertOk();

        $this->assertSame('Ana Quispe Rojas', $usuario->refresh()->nombre);
    }

    public function test_editar_un_usuario_inexistente_devuelve_no_encontrado(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->patchJson('/usuarios/9999', ['nombre' => 'Quien Sea'])
            ->assertStatus(404)
            ->assertJsonPath('error.codigo', 'RECURSO_NO_ENCONTRADO');
    }

    public function test_el_administrador_no_puede_quitarse_su_propio_rol_por_http(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)
            ->patchJson("/usuarios/{$admin->id}", ['rol' => Usuario::ROL_VENDEDOR])
            ->assertStatus(403);

        $this->assertSame(Usuario::ROL_ADMINISTRADOR, $admin->refresh()->rol);
    }

    public static function formasDeFalso(): array
    {
        return [
            'booleano' => [false],
            'entero' => [0],
            'cadena, que es lo que envía un formulario' => ['0'],
        ];
    }

    /**
     * Por HTTP y con las tres formas que la regla `boolean` admite. Con `0` y
     * `"0"` el administrador llegaba a desactivarse, y ahí el sistema se queda
     * sin nadie que pueda autenticarse para reactivar a nadie: no hay vuelta
     * atrás desde la aplicación.
     */
    #[DataProvider('formasDeFalso')]
    public function test_el_administrador_no_puede_desactivarse_por_http(mixed $falso): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)
            ->patchJson("/usuarios/{$admin->id}", ['activo' => $falso])
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');

        $this->assertTrue($admin->refresh()->activo);
    }

    public function test_el_administrador_si_puede_desactivar_a_otro_por_http(): void
    {
        $otro = Usuario::factory()->administrador()->create();

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->patchJson("/usuarios/{$otro->id}", ['activo' => '0'])
            ->assertOk();

        $this->assertFalse($otro->refresh()->activo);
    }

    public function test_el_vendedor_no_puede_editar_usuarios(): void
    {
        $usuario = Usuario::factory()->create();

        $this->actingAs(Usuario::factory()->create())
            ->patchJson("/usuarios/{$usuario->id}", ['nombre' => 'Nombre Cambiado'])
            ->assertStatus(403);
    }
}
