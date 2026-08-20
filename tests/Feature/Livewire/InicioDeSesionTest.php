<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Usuarios\Livewire\InicioDeSesion;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-02. La pantalla de acceso y sus cuatro estados visibles: en reposo,
 * enviando, error de credenciales y éxito.
 */
final class InicioDeSesionTest extends TestCase
{
    use RefreshDatabase;

    // --- La pantalla responde de verdad ---

    /**
     * Que la ruta no rechace no alcanza: una ruta inexistente tampoco
     * rechaza, devuelve 404. Se comprueba que la pantalla se sirve y que trae
     * el formulario, no solo que el control de acceso la deja pasar.
     */
    public function test_la_pantalla_de_acceso_se_sirve_a_un_anonimo(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk();
        $respuesta->assertSee('Contraseña');
        $respuesta->assertSee('campo-email', false);
    }

    public function test_la_pantalla_de_acceso_no_muestra_el_menu(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Menú principal')
            ->assertDontSee('Cerrar sesión');
    }

    public function test_quien_ya_tiene_sesion_no_vuelve_a_la_pantalla_de_acceso(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->get('/login')
            ->assertRedirect('/panel');
    }

    // --- Estados del componente ---

    public function test_en_reposo_no_muestra_ningun_error(): void
    {
        Livewire::test(InicioDeSesion::class)
            ->assertOk()
            ->assertSet('error', null)
            ->assertDontSee('data-prueba="aviso"', false);
    }

    /** El estado "enviando" bloquea el botón mientras dura la operación. */
    public function test_el_boton_se_bloquea_mientras_envia(): void
    {
        Livewire::test(InicioDeSesion::class)
            ->assertSeeHtml('wire:loading.attr="disabled"')
            ->assertSeeHtml('wire:target="iniciar"');
    }

    public function test_con_credenciales_validas_inicia_sesion_y_va_al_panel(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->call('iniciar')
            ->assertRedirect('/panel');

        $this->assertAuthenticated();
    }

    /** El correo se normaliza antes de comparar, como promete el contrato. */
    public function test_el_correo_con_mayusculas_y_espacios_igual_inicia_sesion(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        Livewire::test(InicioDeSesion::class)
            ->set('email', '  ANA@Ejemplo.PE  ')
            ->set('password', 'contrasena-valida')
            ->call('iniciar')
            ->assertRedirect('/panel');

        $this->assertAuthenticated();
    }

    public function test_credenciales_invalidas_muestran_un_solo_mensaje(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $componente = Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'equivocada')
            ->call('iniciar')
            ->assertNoRedirect();

        $this->assertGuest();
        $this->assertNotNull($componente->get('error'));

        // Un solo aviso, no uno por causa posible.
        $this->assertSame(1, substr_count($componente->html(), 'data-prueba="aviso"'));
    }

    /** El foco vuelve al campo de correo, que es donde se corrige el dato. */
    public function test_tras_un_fallo_el_foco_vuelve_al_correo(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'equivocada')
            ->call('iniciar')
            ->assertDispatched('foco-al-correo');
    }

    public function test_la_contrasena_no_sobrevive_a_un_intento_fallido(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'equivocada')
            ->call('iniciar')
            ->assertSet('password', '');
    }

    /**
     * Las tres causas —correo inexistente, usuario inactivo y contraseña
     * equivocada— dicen exactamente lo mismo. Si la pantalla las distinguiera,
     * desharía la decisión que el servicio toma a propósito.
     */
    public function test_las_tres_causas_devuelven_el_mismo_mensaje(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);
        Usuario::factory()->inactivo()->create(['email' => 'baja@ejemplo.pe', 'password' => 'contrasena-valida']);

        $mensajes = [];

        foreach ([
            ['ana@ejemplo.pe', 'equivocada'],
            ['baja@ejemplo.pe', 'contrasena-valida'],
            ['nadie@ejemplo.pe', 'contrasena-valida'],
        ] as [$correo, $clave]) {
            $mensajes[] = Livewire::test(InicioDeSesion::class)
                ->set('email', $correo)
                ->set('password', $clave)
                ->call('iniciar')
                ->get('error');
        }

        $this->assertCount(1, array_unique($mensajes), 'Los tres fallos deben decir lo mismo.');
        $this->assertGuest();
    }

    /** El código de la taxonomía se registra, pero nunca se le muestra a quien opera. */
    public function test_el_mensaje_no_expone_el_codigo_de_error(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        $componente = Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'equivocada')
            ->call('iniciar');

        $this->assertStringNotContainsString('CREDENCIALES_INVALIDAS', $componente->html());
    }

    // --- Recuperación de la URL pedida sin sesión ---

    /**
     * Criterio de cierre de UT-02, y regla transversal de experiencia.md.
     *
     * Recorre las dos capas: el middleware guarda la intención al rechazar, y
     * este componente la consume con `redirectIntended`. Ninguna de las dos
     * sirve sola, así que se prueban juntas y de punta a punta.
     */
    public function test_la_url_pedida_sin_sesion_se_recupera_tras_iniciar_sesion(): void
    {
        Usuario::factory()->administrador()->create([
            'email' => 'ada@ejemplo.pe',
            'password' => 'contrasena-valida',
        ]);

        // Sin sesión se pide una pantalla protegida: no es un callejón sin
        // salida, lleva al acceso.
        $this->get('/usuarios')->assertRedirect('/login');

        // Tras entrar se vuelve a lo que se había pedido, no al panel.
        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ada@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->call('iniciar')
            ->assertRedirect('/usuarios');

        $this->assertAuthenticated();
    }

    /** Sin URL pedida previa, el destino por defecto sigue siendo el panel. */
    public function test_sin_url_pedida_previa_va_al_panel(): void
    {
        Usuario::factory()->create(['email' => 'ana@ejemplo.pe', 'password' => 'contrasena-valida']);

        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ana@ejemplo.pe')
            ->set('password', 'contrasena-valida')
            ->call('iniciar')
            ->assertRedirect('/panel');
    }

    /**
     * Un intento fallido no puede perder la intención guardada: si se borrara,
     * equivocarse una vez mandaría al panel en vez de a donde se iba.
     */
    public function test_un_intento_fallido_no_pierde_la_url_pedida(): void
    {
        Usuario::factory()->administrador()->create([
            'email' => 'ada@ejemplo.pe',
            'password' => 'contrasena-valida',
        ]);

        $this->get('/usuarios')->assertRedirect('/login');

        Livewire::test(InicioDeSesion::class)
            ->set('email', 'ada@ejemplo.pe')
            ->set('password', 'equivocada')
            ->call('iniciar')
            ->assertNoRedirect()
            ->set('password', 'contrasena-valida')
            ->call('iniciar')
            ->assertRedirect('/usuarios');
    }
}
