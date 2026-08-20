<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Proveedores\Livewire\ListaDeProveedores;
use App\Dominios\Proveedores\Modelos\Proveedor;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-03. Proveedores: el RUC se marca en el momento, el listado pagina y el
 * filtro vive en la URL.
 */
final class ListaDeProveedoresTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdministrador(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
    }

    public function test_la_pantalla_se_sirve_al_administrador(): void
    {
        $this->comoAdministrador();
        Proveedor::factory()->create(['razon_social' => 'Distribuidora del Sur']);

        $this->get('/proveedores')
            ->assertOk()
            ->assertSee('Distribuidora del Sur')
            ->assertSee('Nuevo proveedor');
    }

    // --- El RUC se marca en el momento ---

    public function test_un_ruc_de_longitud_incorrecta_se_marca_al_escribir(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '2012345')
            ->assertNotSet('avisoDeDocumento', null)
            ->assertSeeHtml('data-prueba="aviso-de-documento"');
    }

    public function test_el_aviso_desaparece_con_un_ruc_de_once_digitos(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '2012345')
            ->assertNotSet('avisoDeDocumento', null)
            ->set('numeroDocumento', '20123456789')
            ->assertSet('avisoDeDocumento', null);
    }

    public function test_un_campo_vacio_no_dispara_el_aviso(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '')
            ->assertSet('avisoDeDocumento', null);
    }

    /** Once caracteres pero con letras: la longitud sola no alcanza. */
    public function test_un_ruc_con_letras_se_marca_aunque_mida_once(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '2012345678A')
            ->assertNotSet('avisoDeDocumento', null);
    }

    /**
     * El aviso es comodidad: el servidor rechaza la misma entrada aunque se
     * evite, y con su propio código junto al campo.
     */
    public function test_el_servidor_rechaza_el_ruc_aunque_el_aviso_se_evite(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '2012345')
            ->set('razonSocial', 'Distribuidora del Sur')
            ->call('crear')
            ->assertSet('campoConError', 'numero_documento')
            ->assertSet('exito', null);

        $this->assertDatabaseCount('proveedores', 0);
    }

    // --- Los tres códigos del documento, en su orden ---

    public function test_falta_el_numero_se_distingue_de_formato_invalido(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '')
            ->set('razonSocial', 'Distribuidora del Sur')
            ->call('crear')
            ->assertSet('campoConError', 'numero_documento')
            ->assertSee('Falta el número');
    }

    /**
     * Un número a la vez inválido y repetido informa el formato, no el
     * duplicado: el orden entre los tres códigos está fijado, y si se
     * invirtiera la persona cambiaría el número en vez de corregirlo.
     */
    public function test_un_ruc_invalido_y_repetido_informa_el_formato(): void
    {
        $this->comoAdministrador();
        Proveedor::factory()->create(['numero_documento' => '20123456789']);

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '2012345678')
            ->set('razonSocial', 'Otra empresa')
            ->call('crear')
            ->assertSee('no tiene el formato esperado')
            ->assertDontSee('Ya existe');
    }

    public function test_un_ruc_valido_y_repetido_informa_el_duplicado(): void
    {
        $this->comoAdministrador();
        Proveedor::factory()->create(['numero_documento' => '20123456789']);

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '20123456789')
            ->set('razonSocial', 'Otra empresa')
            ->call('crear')
            ->assertSet('campoConError', 'numero_documento')
            ->assertSee('Ya existe');
    }

    // --- Alta y edición ---

    public function test_el_alta_crea_el_proveedor(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->call('nuevo')
            ->set('numeroDocumento', '20123456789')
            ->set('razonSocial', 'Distribuidora del Sur')
            ->call('crear')
            ->assertSee('Proveedor creado');

        $this->assertDatabaseHas('proveedores', ['numero_documento' => '20123456789']);
    }

    public function test_la_edicion_no_ofrece_cambiar_el_documento(): void
    {
        $this->comoAdministrador();
        $proveedor = Proveedor::factory()->create(['numero_documento' => '20123456789']);

        Livewire::test(ListaDeProveedores::class)
            ->call('editar', $proveedor->id)
            ->assertSee('no se puede cambiar')
            ->assertDontSeeHtml('id="campo-numero_documento"');
    }

    public function test_la_edicion_guarda_la_razon_social(): void
    {
        $this->comoAdministrador();
        $proveedor = Proveedor::factory()->create(['razon_social' => 'Sur']);

        Livewire::test(ListaDeProveedores::class)
            ->call('editar', $proveedor->id)
            ->set('razonSocial', 'Distribuidora del Sur')
            ->call('actualizar')
            ->assertSee('Proveedor actualizado');

        $this->assertSame('Distribuidora del Sur', $proveedor->refresh()->razon_social);
    }

    // --- Listado, paginación y URL ---

    public function test_el_listado_pagina_y_conserva_el_filtro_en_la_url(): void
    {
        $this->comoAdministrador();

        Livewire::withQueryParams(['buscar' => 'sur', 'pagina' => 2])
            ->test(ListaDeProveedores::class)
            ->assertSet('buscar', 'sur')
            ->assertSet('pagina', 2);
    }

    public function test_cambiar_la_busqueda_vuelve_a_la_primera_pagina(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProveedores::class)
            ->set('pagina', 3)
            ->set('buscar', 'sur')
            ->assertSet('pagina', 1);
    }

    public function test_los_inactivos_solo_aparecen_si_se_piden(): void
    {
        $this->comoAdministrador();
        Proveedor::factory()->create(['razon_social' => 'Cesada', 'activo' => false]);

        Livewire::test(ListaDeProveedores::class)
            ->assertDontSee('Cesada')
            ->set('incluirInactivos', true)
            ->assertSee('Cesada');
    }

    // --- Protección, por URL y no por el menú ---

    public function test_un_vendedor_no_entra_por_url_directa(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]));

        $this->get('/proveedores')->assertStatus(403);
    }

    public function test_un_anonimo_va_al_acceso(): void
    {
        $this->get('/proveedores')->assertRedirect('/login');
    }
}
