<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Catalogo\Livewire\ListaDeCategorias;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-01. Categorías: listado, alta y edición en la misma pantalla, con los
 * cuatro estados y el cambio de estado reflejado sin recargar.
 */
final class ListaDeCategoriasTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdministrador(): Usuario
    {
        $admin = Usuario::factory()->administrador()->create();

        $this->actingAs($admin);

        return $admin;
    }

    // --- La pantalla responde ---

    public function test_la_pantalla_se_sirve_al_administrador_con_su_listado(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Abarrotes']);

        $this->get('/categorias')
            ->assertOk()
            ->assertSee('Abarrotes')
            ->assertSee('Nueva categoría');
    }

    // --- Cuatro estados ---

    public function test_estado_vacio_cuando_no_hay_categorias(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeCategorias::class)
            ->assertSee('Todavía no hay categorías')
            ->assertDontSeeHtml('data-prueba="fila-categoria"');
    }

    public function test_estado_con_datos(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Abarrotes']);

        Livewire::test(ListaDeCategorias::class)->assertSee('Abarrotes');
    }

    public function test_estado_de_exito_tras_crear(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeCategorias::class)
            ->call('nuevo')
            ->set('nombre', 'Bebidas')
            ->call('crear')
            ->assertSee('Categoría creada')
            ->assertSet('error', null);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Bebidas']);
    }

    /** El nombre repetido se señala junto al campo, no como aviso suelto. */
    public function test_estado_de_error_junto_al_campo_si_el_nombre_se_repite(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Abarrotes']);

        Livewire::test(ListaDeCategorias::class)
            ->call('nuevo')
            ->set('nombre', 'Abarrotes')
            ->call('crear')
            ->assertSet('campoConError', 'nombre')
            ->assertSet('error', null)
            ->assertSeeHtml('data-prueba="error-de-campo"');
    }

    /**
     * El servicio compara sin distinguir mayúsculas, así que la pantalla
     * tiene que mostrar el rechazo también en ese caso.
     */
    public function test_el_nombre_repetido_se_detecta_sin_distinguir_mayusculas(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Abarrotes']);

        Livewire::test(ListaDeCategorias::class)
            ->call('nuevo')
            ->set('nombre', 'ABARROTES')
            ->call('crear')
            ->assertSet('campoConError', 'nombre');
    }

    /**
     * Un nombre demasiado corto llega con CAMPO_FUERA_DE_RANGO, que la fila
     * del contrato no enumera porque es uno de los tres genéricos que la
     * regla declara para todo método que recibe datos. Igual nombra su campo,
     * que es lo que la pantalla necesita.
     */
    public function test_un_nombre_demasiado_corto_se_señala_junto_al_campo(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeCategorias::class)
            ->call('nuevo')
            ->set('nombre', 'A')
            ->call('crear')
            ->assertSet('campoConError', 'nombre');

        $this->assertDatabaseCount('categorias', 0);
    }

    // --- Alta y edición en la misma pantalla ---

    public function test_la_edicion_carga_los_datos_y_no_cambia_de_ruta(): void
    {
        $this->comoAdministrador();
        $categoria = Categoria::factory()->create(['nombre' => 'Abarrotes', 'descripcion' => 'Secos']);

        Livewire::test(ListaDeCategorias::class)
            ->call('editar', $categoria->id)
            ->assertSet('editando', $categoria->id)
            ->assertSet('nombre', 'Abarrotes')
            ->assertSet('descripcion', 'Secos')
            ->assertSet('formularioAbierto', true)
            ->assertNoRedirect();
    }

    public function test_la_edicion_guarda_el_cambio(): void
    {
        $this->comoAdministrador();
        $categoria = Categoria::factory()->create(['nombre' => 'Abarrotes']);

        Livewire::test(ListaDeCategorias::class)
            ->call('editar', $categoria->id)
            ->set('nombre', 'Abarrotes secos')
            ->call('actualizar')
            ->assertSee('Categoría actualizada');

        $this->assertSame('Abarrotes secos', $categoria->refresh()->nombre);
    }

    public function test_cancelar_cierra_el_formulario_sin_guardar(): void
    {
        $this->comoAdministrador();
        $categoria = Categoria::factory()->create(['nombre' => 'Abarrotes']);

        Livewire::test(ListaDeCategorias::class)
            ->call('editar', $categoria->id)
            ->set('nombre', 'Otro nombre')
            ->call('cancelar')
            ->assertSet('formularioAbierto', false)
            ->assertSet('editando', null);

        $this->assertSame('Abarrotes', $categoria->refresh()->nombre);
    }

    // --- Desactivar se refleja sin recargar ---

    public function test_desactivar_se_refleja_en_la_lista_sin_recargar(): void
    {
        $this->comoAdministrador();
        $categoria = Categoria::factory()->create(['nombre' => 'Abarrotes', 'activo' => true]);

        $componente = Livewire::test(ListaDeCategorias::class)->assertSee('Activa');

        $componente->call('cambiarEstado', $categoria->id, false)
            ->assertSee('Categoría desactivada')
            ->assertNoRedirect();

        $this->assertFalse($categoria->refresh()->activo);

        // La fila ya no dice "Activa" sin que nadie haya recargado la página:
        // el propio render vuelve a consultar.
        $componente->set('incluirInactivas', true)->assertSee('Inactiva');
    }

    public function test_una_categoria_inactiva_no_aparece_salvo_que_se_pidan(): void
    {
        $this->comoAdministrador();
        Categoria::factory()->create(['nombre' => 'Descontinuada', 'activo' => false]);

        Livewire::test(ListaDeCategorias::class)
            ->assertDontSee('Descontinuada')
            ->set('incluirInactivas', true)
            ->assertSee('Descontinuada');
    }

    /** El filtro vive en la URL para que recargar y compartir el enlace coincidan. */
    public function test_el_filtro_vive_en_la_url(): void
    {
        $this->comoAdministrador();

        Livewire::withQueryParams(['incluirInactivas' => true])
            ->test(ListaDeCategorias::class)
            ->assertSet('incluirInactivas', true);
    }

    // --- El menú ---

    public function test_el_administrador_ve_la_seccion_en_el_menu(): void
    {
        $this->comoAdministrador();

        $this->get('/categorias')->assertSee('Categorías');
    }

    /**
     * El vendedor no administra el catálogo. No se comprueba por el menú: se
     * entra por la URL, que es por donde llegaría alguien con un enlace viejo.
     */
    public function test_un_vendedor_no_entra_por_url_directa(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]));

        $this->get('/categorias')->assertStatus(403);
    }

    public function test_un_anonimo_va_al_acceso(): void
    {
        $this->get('/categorias')->assertRedirect('/login');
    }
}
