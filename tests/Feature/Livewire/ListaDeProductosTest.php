<?php

namespace Tests\Feature\Livewire;

use App\Dominios\Catalogo\Livewire\ListaDeProductos;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UT-02. Productos: los dos precios, el aviso inmediato cuando el mayor supera
 * al menor, y el rechazo del servidor aunque ese aviso se evite.
 */
final class ListaDeProductosTest extends TestCase
{
    use RefreshDatabase;

    private function comoAdministrador(): Usuario
    {
        $admin = Usuario::factory()->administrador()->create();
        $this->actingAs($admin);

        return $admin;
    }

    /** @return array{0: Categoria, 1: array<string, mixed>} */
    private function categoriaYFormulario(): array
    {
        $categoria = Categoria::factory()->create(['nombre' => 'Abarrotes']);

        return [$categoria, [
            'codigo' => 'ARROZ-01',
            'nombre' => 'Arroz extra',
            'categoriaDelFormulario' => $categoria->id,
            'unidadMedida' => 'KGM',
            'precioMenor' => '10.00',
            'precioMayor' => '8.00',
            'stockMinimo' => '5',
        ]];
    }

    // --- La pantalla responde ---

    public function test_la_pantalla_se_sirve_al_administrador(): void
    {
        $this->comoAdministrador();
        Producto::factory()->create(['nombre' => 'Arroz extra']);

        $this->get('/productos')
            ->assertOk()
            ->assertSee('Arroz extra')
            ->assertSee('Nuevo producto');
    }

    /**
     * Un producto no tiene existencias hasta que una compra cree su lote. La
     * pantalla no debe inventar un cero: diría "sin stock" sobre algo que
     * nunca se compró, y eso es indistinguible de un producto agotado.
     */
    public function test_la_pantalla_no_muestra_existencias(): void
    {
        $this->comoAdministrador();
        Producto::factory()->create(['nombre' => 'Arroz extra']);

        $html = $this->get('/productos')->getContent();

        $this->assertStringNotContainsString('Stock disponible', $html);
        $this->assertStringNotContainsString('Existencias', $html);
    }

    // --- Aviso inmediato de precios ---

    public function test_el_aviso_de_precios_aparece_al_escribir(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProductos::class)
            ->call('nuevo')
            ->set('precioMenor', '10.00')
            ->set('precioMayor', '12.00')
            ->assertSet('avisoDePrecios', 'El precio al por mayor no puede superar al precio al por menor.')
            ->assertSeeHtml('data-prueba="aviso-de-precios"');
    }

    public function test_el_aviso_desaparece_cuando_los_precios_se_corrigen(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProductos::class)
            ->call('nuevo')
            ->set('precioMenor', '10.00')
            ->set('precioMayor', '12.00')
            ->assertNotSet('avisoDePrecios', null)
            ->set('precioMayor', '9.00')
            ->assertSet('avisoDePrecios', null);
    }

    /** Un precio a medio escribir no es un precio inválido: no se avisa nada. */
    public function test_un_precio_incompleto_no_dispara_el_aviso(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProductos::class)
            ->call('nuevo')
            ->set('precioMenor', '10.00')
            ->set('precioMayor', '')
            ->assertSet('avisoDePrecios', null)
            ->set('precioMayor', '1')
            ->assertSet('avisoDePrecios', null);
    }

    /**
     * Precios iguales son válidos: la regla es que el mayor no SUPERE al
     * menor. Comparar con el operador equivocado los rechazaría.
     */
    public function test_precios_iguales_no_disparan_el_aviso(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProductos::class)
            ->call('nuevo')
            ->set('precioMenor', '10.0000')
            ->set('precioMayor', '10.0000')
            ->assertSet('avisoDePrecios', null);
    }

    /**
     * Criterio de cierre de UT-02: el servidor vuelve a rechazar la misma
     * combinación aunque la pantalla la haya avisado. El aviso es comodidad;
     * la protección es del servicio, y se comprueba invocando la operación
     * directamente, sin pasar por el aviso.
     */
    public function test_el_servidor_rechaza_los_precios_aunque_el_aviso_se_evite(): void
    {
        $this->comoAdministrador();
        [, $formulario] = $this->categoriaYFormulario();

        $componente = Livewire::test(ListaDeProductos::class)->call('nuevo');

        foreach ($formulario as $propiedad => $valor) {
            $componente->set($propiedad, $valor);
        }

        // Se invierten los precios y se llama a crear sin mirar el aviso.
        $componente->set('precioMenor', '8.00')->set('precioMayor', '10.00')
            ->call('crear')
            ->assertSet('campoConError', 'precio_mayor')
            ->assertSet('exito', null);

        $this->assertDatabaseCount('productos', 0);
    }

    // --- Alta y edición ---

    public function test_el_alta_crea_el_producto(): void
    {
        $this->comoAdministrador();
        [, $formulario] = $this->categoriaYFormulario();

        $componente = Livewire::test(ListaDeProductos::class)->call('nuevo');
        foreach ($formulario as $propiedad => $valor) {
            $componente->set($propiedad, $valor);
        }

        $componente->call('crear')->assertSee('Producto creado');

        $this->assertDatabaseHas('productos', ['codigo' => 'ARROZ-01']);
    }

    public function test_el_codigo_repetido_se_señala_junto_al_codigo(): void
    {
        $this->comoAdministrador();
        [, $formulario] = $this->categoriaYFormulario();
        Producto::factory()->create(['codigo' => 'ARROZ-01']);

        $componente = Livewire::test(ListaDeProductos::class)->call('nuevo');
        foreach ($formulario as $propiedad => $valor) {
            $componente->set($propiedad, $valor);
        }

        $componente->call('crear')
            ->assertSet('campoConError', 'codigo')
            ->assertSet('error', null);
    }

    /** El código no se puede cambiar, así que la edición no lo ofrece. */
    public function test_la_edicion_no_ofrece_cambiar_el_codigo(): void
    {
        $this->comoAdministrador();
        $producto = Producto::factory()->create(['codigo' => 'ARROZ-01']);

        Livewire::test(ListaDeProductos::class)
            ->call('editar', $producto->id)
            ->assertSet('codigo', 'ARROZ-01')
            ->assertSee('no se puede cambiar')
            ->assertDontSeeHtml('id="campo-codigo"');
    }

    public function test_la_edicion_guarda_el_cambio_de_precio(): void
    {
        $this->comoAdministrador();
        $producto = Producto::factory()->create(['precio_menor' => '10.0000', 'precio_mayor' => '8.0000']);

        Livewire::test(ListaDeProductos::class)
            ->call('editar', $producto->id)
            ->set('precioMenor', '12.0000')
            ->call('actualizar')
            ->assertSee('Producto actualizado');

        $this->assertSame('12.0000', $producto->refresh()->precio_menor);
    }

    /** Una unidad fuera del catálogo de SUNAT se rechaza junto a su campo. */
    public function test_una_unidad_invalida_se_señala_junto_al_campo(): void
    {
        $this->comoAdministrador();
        [, $formulario] = $this->categoriaYFormulario();

        $componente = Livewire::test(ListaDeProductos::class)->call('nuevo');
        foreach ($formulario as $propiedad => $valor) {
            $componente->set($propiedad, $valor);
        }

        $componente->set('unidadMedida', 'XXX')
            ->call('crear')
            ->assertSet('campoConError', 'unidad_medida');
    }

    /** Las unidades que ofrece el formulario salen del catálogo, no de una lista propia. */
    public function test_las_unidades_ofrecidas_salen_del_catalogo_de_sunat(): void
    {
        $this->comoAdministrador();

        $html = Livewire::test(ListaDeProductos::class)->call('nuevo')->html();

        foreach (['NIU', 'KGM', 'LTR'] as $unidad) {
            $this->assertStringContainsString('value="'.$unidad.'"', $html);
        }
    }

    // --- Listado, filtros y estado en la URL ---

    public function test_la_busqueda_filtra_por_codigo_y_por_nombre(): void
    {
        $this->comoAdministrador();
        Producto::factory()->create(['codigo' => 'ARROZ-01', 'nombre' => 'Arroz extra']);
        Producto::factory()->create(['codigo' => 'AZUCAR-01', 'nombre' => 'Azúcar rubia']);

        Livewire::test(ListaDeProductos::class)
            ->set('buscar', 'ARROZ')
            ->assertSee('Arroz extra')
            ->assertDontSee('Azúcar rubia');
    }

    public function test_el_filtro_de_categoria_acota_el_listado(): void
    {
        $this->comoAdministrador();
        $abarrotes = Categoria::factory()->create(['nombre' => 'Abarrotes']);
        $bebidas = Categoria::factory()->create(['nombre' => 'Bebidas']);
        Producto::factory()->create(['nombre' => 'Arroz extra', 'categoria_id' => $abarrotes->id]);
        Producto::factory()->create(['nombre' => 'Gaseosa', 'categoria_id' => $bebidas->id]);

        Livewire::test(ListaDeProductos::class)
            ->set('categoriaId', $abarrotes->id)
            ->assertSee('Arroz extra')
            ->assertDontSee('Gaseosa');
    }

    public function test_los_filtros_y_la_pagina_viven_en_la_url(): void
    {
        $this->comoAdministrador();

        Livewire::withQueryParams(['buscar' => 'arroz', 'pagina' => 2])
            ->test(ListaDeProductos::class)
            ->assertSet('buscar', 'arroz')
            ->assertSet('pagina', 2);
    }

    public function test_cambiar_la_busqueda_vuelve_a_la_primera_pagina(): void
    {
        $this->comoAdministrador();

        Livewire::test(ListaDeProductos::class)
            ->set('pagina', 3)
            ->set('buscar', 'arroz')
            ->assertSet('pagina', 1);
    }

    public function test_desactivar_se_refleja_sin_recargar(): void
    {
        $this->comoAdministrador();
        $producto = Producto::factory()->create(['nombre' => 'Arroz extra', 'activo' => true]);

        Livewire::test(ListaDeProductos::class)
            ->assertSee('Activo')
            ->call('cambiarEstado', $producto->id, false)
            ->assertSee('Producto desactivado')
            ->assertSee('Inactivo')
            ->assertNoRedirect();

        $this->assertFalse($producto->refresh()->activo);
    }

    // --- Protección, comprobada por URL y no por el menú ---

    public function test_un_vendedor_no_entra_por_url_directa(): void
    {
        $this->actingAs(Usuario::factory()->create(['rol' => Usuario::ROL_VENDEDOR]));

        $this->get('/productos')->assertStatus(403);
    }

    public function test_un_anonimo_va_al_acceso(): void
    {
        $this->get('/productos')->assertRedirect('/login');
    }
}
