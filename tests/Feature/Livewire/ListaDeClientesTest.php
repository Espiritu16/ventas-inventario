<?php

namespace Tests\Feature\Livewire;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Dominios\Clientes\Livewire\ListaDeClientes;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-04. Clientes: la validación del documento cambia según el tipo elegido, y
 * el componente se puede montar como diálogo desde otra pantalla devolviendo
 * el cliente creado.
 */
final class ListaDeClientesTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdministrador(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create());
    }

    private function comoVendedor(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]));
    }

    public function test_la_pantalla_se_sirve_y_el_vendedor_tambien_entra(): void
    {
        Cliente::factory()->create(['nombre' => 'Ana Quispe']);

        $this->comoAdministrador();
        $this->get('/clientes')->assertOk()->assertSee('Ana Quispe');

        // Atender clientes es parte de vender: la matriz se lo permite.
        $this->comoVendedor();
        $this->get('/clientes')->assertOk()->assertSee('Ana Quispe');
    }

    // --- La validación cambia según el tipo ---

    public function test_ocho_digitos_valen_para_dni_y_no_para_ruc(): void
    {
        $this->comoAdministrador();

        $componente = Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '12345678')
            ->assertSet('avisoDeDocumento', null);

        // El mismo número, con otro tipo, deja de ser válido.
        $componente->set('tipoDocumento', TipoDeDocumento::RUC->value)
            ->assertNotSet('avisoDeDocumento', null);
    }

    public function test_once_digitos_valen_para_ruc(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::RUC->value)
            ->set('numeroDocumento', '20123456789')
            ->assertSet('avisoDeDocumento', null);
    }

    public function test_sin_documento_no_admite_numero(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::SIN_DOCUMENTO->value)
            ->set('numeroDocumento', '12345678')
            ->assertNotSet('avisoDeDocumento', null);
    }

    /** El carné de extranjería admite letras, a diferencia del DNI. */
    public function test_el_carne_de_extranjeria_admite_letras(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::CARNE_DE_EXTRANJERIA->value)
            ->set('numeroDocumento', 'AB12345')
            ->assertSet('avisoDeDocumento', null);
    }

    public function test_el_servidor_rechaza_el_documento_aunque_el_aviso_se_evite(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '1234')
            ->set('nombre', 'Ana Quispe')
            ->call('crear')
            ->assertSet('campoConError', 'numero_documento')
            ->assertSet('exito', null);

        $this->assertDatabaseCount('clientes', 0);
    }

    // --- Alta ---

    public function test_el_alta_crea_el_cliente(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '12345678')
            ->set('nombre', 'Ana Quispe')
            ->call('crear')
            ->assertSee('Cliente creado');

        $this->assertDatabaseHas('clientes', ['numero_documento' => '12345678']);
    }

    public function test_un_cliente_sin_documento_se_crea_sin_numero(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::SIN_DOCUMENTO->value)
            ->set('numeroDocumento', '')
            ->set('nombre', 'Cliente de mostrador')
            ->call('crear')
            ->assertSee('Cliente creado');

        $this->assertDatabaseHas('clientes', ['nombre' => 'Cliente de mostrador', 'numero_documento' => null]);
    }

    public function test_un_documento_repetido_se_señala_junto_al_campo(): void
    {
        $this->comoAdministrador();
        Cliente::factory()->create(['numero_documento' => '12345678']);

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '12345678')
            ->set('nombre', 'Otra Ana')
            ->call('crear')
            ->assertSet('campoConError', 'numero_documento')
            ->assertSee('Ya existe');
    }

    // --- Reutilizable como diálogo desde otra pantalla ---

    public function test_como_dialogo_muestra_solo_el_formulario(): void
    {
        $this->comoVendedor();
        Cliente::factory()->create(['nombre' => 'Ana Quispe']);

        Livewire::test(ListaDeClientes::class, ['comoDialogo' => true])
            ->assertSet('formularioAbierto', true)
            ->assertDontSee('Ana Quispe')
            ->assertDontSeeHtml('data-prueba="buscar"');
    }

    /**
     * Criterio de cierre de UT-04: quien lo abrió necesita el cliente
     * seleccionado, así que se le devuelve el identificador en vez de
     * obligarlo a buscarlo otra vez.
     */
    public function test_como_dialogo_devuelve_el_cliente_creado(): void
    {
        $this->comoVendedor();

        Livewire::test(ListaDeClientes::class, ['comoDialogo' => true])
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '12345678')
            ->set('nombre', 'Ana Quispe')
            ->call('crear')
            ->assertDispatched('cliente-creado');

        $cliente = Cliente::query()->where('numero_documento', '12345678')->firstOrFail();

        $this->assertSame('Ana Quispe', $cliente->nombre);
    }

    public function test_como_pantalla_no_emite_el_evento_del_dialogo(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeClientes::class)
            ->call('nuevo')
            ->set('tipoDocumento', TipoDeDocumento::DNI->value)
            ->set('numeroDocumento', '12345678')
            ->set('nombre', 'Ana Quispe')
            ->call('crear')
            ->assertNotDispatched('cliente-creado');
    }

    public function test_cancelar_el_dialogo_avisa_a_quien_lo_abrio(): void
    {
        $this->comoVendedor();

        Livewire::test(ListaDeClientes::class, ['comoDialogo' => true])
            ->call('cancelar')
            ->assertDispatched('alta-de-cliente-cancelada');
    }

    // --- Editar es del administrador ---

    public function test_el_vendedor_no_ve_el_boton_de_editar(): void
    {
        Cliente::factory()->create(['nombre' => 'Ana Quispe']);

        $this->comoVendedor();

        Livewire::test(ListaDeClientes::class)->assertDontSeeHtml('data-prueba="editar-cliente"');
    }

    /**
     * Y que no lo vea no es lo que lo protege: invocar el método igual se
     * rechaza, que es por donde llegaría alguien que no pasa por el botón.
     */
    public function test_el_vendedor_tampoco_puede_editar_invocando_el_metodo(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Ana Quispe']);

        $this->comoVendedor();

        $lanzo = false;

        try {
            Livewire::test(ListaDeClientes::class)
                ->call('editar', $cliente->id)
                ->set('nombre', 'Nombre cambiado')
                ->call('actualizar');
        } catch (\Throwable) {
            $lanzo = true;
        }

        $this->assertTrue($lanzo, 'El vendedor no debería poder actualizar un cliente.');
        $this->assertSame('Ana Quispe', $cliente->refresh()->nombre);
    }

    public function test_el_administrador_si_edita(): void
    {
        $this->comoAdministrador();
        $cliente = Cliente::factory()->create(['nombre' => 'Ana Quispe']);

        Livewire::test(ListaDeClientes::class)
            ->call('editar', $cliente->id)
            ->set('nombre', 'Ana Quispe Rojas')
            ->call('actualizar')
            ->assertSee('Cliente actualizado');

        $this->assertSame('Ana Quispe Rojas', $cliente->refresh()->nombre);
    }

    public function test_un_anonimo_va_al_acceso(): void
    {
        $this->get('/clientes')->assertRedirect('/login');
    }
}
