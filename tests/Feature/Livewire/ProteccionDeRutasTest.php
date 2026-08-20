<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La protección de las pantallas, comprobada por HTTP y **sin mirar el menú**.
 *
 * Ocultar un ítem del menú es comodidad, no control de acceso. Si una prueba
 * de autorización pasara porque el ítem no está visible, estaría comprobando
 * el menú: el día que alguien llegue por URL directa, por un enlace viejo o
 * por el historial, nadie se enteraría. Por eso acá se entra siempre por la
 * URL, nunca navegando.
 */
final class ProteccionDeRutasTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_vendedor_que_escribe_la_url_de_usuarios_es_rechazado(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]))
            ->get('/usuarios')
            ->assertStatus(403);
    }

    public function test_un_anonimo_que_escribe_la_url_de_usuarios_es_rechazado(): void
    {
        $this->get('/usuarios')->assertStatus(401);
    }

    public function test_el_panel_admite_a_los_dos_roles_por_url_directa(): void
    {
        foreach ([Usuario::ROL_ADMINISTRADOR, Usuario::ROL_VENDEDOR] as $rol) {
            $this->actingAs(Usuario::factory()->create(['rol' => $rol]))
                ->get('/panel')
                ->assertOk()
                ->assertSee('Elige una sección');
        }
    }

    public function test_el_panel_no_se_sirve_sin_sesion(): void
    {
        $this->get('/panel')->assertStatus(401);
    }

    /**
     * El vendedor no ve la sección en el menú, pero eso no es lo que lo
     * protege: comprobamos las dos cosas por separado, y que la segunda siga
     * siendo cierta aunque la primera cambiara.
     */
    public function test_el_menu_oculta_y_ademas_el_servidor_rechaza(): void
    {
        $vendedor = Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]);

        // 1) Comodidad: no aparece en el menú del panel.
        $this->actingAs($vendedor)->get('/panel')->assertDontSee('Usuarios');

        // 2) Protección: da igual el menú, la URL directa se rechaza.
        $this->actingAs($vendedor)->get('/usuarios')->assertStatus(403);
    }

    /**
     * Una pantalla declarada no convierte su prefijo en territorio abierto.
     * Backend lo comprueba sobre la matriz; acá se comprueba sobre la ruta
     * real, que es la que responde.
     */
    public function test_la_pantalla_de_acceso_no_abre_su_prefijo(): void
    {
        $this->get('/login/algo')->assertStatus(404);
        $this->patch('/login')->assertStatus(405);
    }

    /** Un usuario desactivado pierde el acceso en la petición siguiente. */
    public function test_un_usuario_desactivado_deja_de_ver_las_pantallas(): void
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin)->get('/usuarios')->assertOk();

        $admin->update(['activo' => false]);

        $this->actingAs($admin)->get('/usuarios')->assertStatus(401);
    }
}
