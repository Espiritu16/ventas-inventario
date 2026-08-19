<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Tests\TestCase;

/**
 * Sección "Rutas que registra Livewire" de la gobernanza.
 *
 * El endpoint de actualización es el canal de todas las interacciones de
 * componentes: si quedara abierto, el control de acceso del sistema pasaría a
 * depender de lo que el paquete haga por su cuenta.
 */
final class RutasDeLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function ruta(string $sufijo): string
    {
        return EndpointResolver::prefix().'/'.$sufijo;
    }

    public function test_el_asset_de_javascript_se_sirve_sin_sesion(): void
    {
        $this->get($this->ruta('livewire.js'))->assertOk();
    }

    public function test_el_endpoint_de_actualizacion_rechaza_sin_sesion(): void
    {
        $this->postJson($this->ruta('update'), ['components' => []])
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    /** Un payload que no se puede leer no se autoriza: lo ilegible se rechaza. */
    public function test_el_endpoint_de_actualizacion_rechaza_un_payload_ilegible_sin_sesion(): void
    {
        $this->postJson($this->ruta('update'), ['components' => [['snapshot' => 'no-es-json']]])
            ->assertStatus(401);
    }

    /**
     * Ningún componente está declarado accesible sin sesión todavía: el de
     * inicio de sesión llega con S-01-F. Hasta entonces, ninguno pasa.
     */
    public function test_ningun_componente_se_invoca_sin_sesion_todavia(): void
    {
        $snapshot = json_encode(['memo' => ['name' => 'usuarios.humo-de-instalacion']]);

        $this->postJson($this->ruta('update'), ['components' => [['snapshot' => $snapshot]]])
            ->assertStatus(401);
    }

    public function test_con_sesion_el_endpoint_de_actualizacion_deja_pasar(): void
    {
        $respuesta = $this->actingAs(Usuario::factory()->create())
            ->postJson($this->ruta('update'), ['components' => []]);

        $this->assertNotSame(401, $respuesta->status(), 'La sesión válida no debería rechazarse.');
        $this->assertNotSame(403, $respuesta->status());
    }

    public function test_la_subida_de_archivos_no_esta_autorizada(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson($this->ruta('upload-file'))
            ->assertStatus(403);
    }

    public function test_la_ruta_de_almacenamiento_no_esta_autorizada(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson('/storage/lo-que-sea.txt')
            ->assertStatus(403);
    }
}
