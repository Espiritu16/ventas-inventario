<?php

namespace Tests\Feature\Autorizacion;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Compartido\Interfaz\RegistroDeComponentesLivewire;
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
     * Un componente que no está en la lista cerrada no se invoca sin sesión,
     * aunque exista y esté registrado.
     */
    public function test_un_componente_fuera_de_la_lista_no_se_invoca_sin_sesion(): void
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

    /**
     * Se comprueba el código de la taxonomía y no solo el status: un 403 lo
     * puede devolver el framework por su cuenta, y entonces la prueba pasaría
     * sin que nuestro control se hubiera ejecutado. Ya ocurrió una vez con
     * `/storage`.
     */
    public function test_la_subida_de_archivos_no_esta_autorizada_ni_para_el_administrador(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson($this->ruta('upload-file'))
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    public function test_la_subida_de_archivos_rechaza_sin_sesion(): void
    {
        $this->postJson($this->ruta('upload-file'))
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    public function test_la_vista_previa_no_esta_autorizada_ni_para_el_administrador(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson($this->ruta('preview-file/lo-que-sea.pdf'))
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    public function test_la_vista_previa_rechaza_sin_sesion(): void
    {
        $this->getJson($this->ruta('preview-file/lo-que-sea.pdf'))
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    public function test_la_ruta_de_almacenamiento_no_esta_autorizada(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson('/storage/lo-que-sea.txt')
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    /**
     * El componente de inicio de sesión está declarado accesible sin sesión,
     * y su nombre se deriva de la clase que fija la gobernanza. Lo construye
     * S-01-F; acá se comprueba que la declaración ya lo reconoce, para que ese
     * sprint no se encuentre con el endpoint cerrado.
     */
    public function test_el_componente_de_inicio_de_sesion_esta_declarado_accesible_sin_sesion(): void
    {
        $this->assertTrue(MatrizDePermisos::componentePuedeInvocarseSinSesion(
            RegistroDeComponentesLivewire::nombreDe('App\Dominios\Usuarios\Livewire\InicioDeSesion')
        ));

        $this->assertFalse(MatrizDePermisos::componentePuedeInvocarseSinSesion('usuarios.humo-de-instalacion'));
    }
}
