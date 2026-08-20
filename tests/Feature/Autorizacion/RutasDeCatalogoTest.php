<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Contrato docs/contratos/productos.md v1 sobre HTTP, con la matriz aplicada a
 * cada ruta: sin sesión, con permiso y con sesión pero sin permiso (RNF-013).
 */
final class RutasDeCatalogoTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Usuario
    {
        return Usuario::factory()->administrador()->create();
    }

    private function vendedor(): Usuario
    {
        return Usuario::factory()->create();
    }

    /** @return array<string, mixed> */
    private function producto(Categoria $categoria): array
    {
        return [
            'codigo' => 'ARR-001',
            'nombre' => 'Arroz extra',
            'categoriaId' => $categoria->id,
            'unidadMedida' => 'KGM',
            'precioMenor' => '5.5000',
            'precioMayor' => '4.8000',
        ];
    }

    // --- Consulta: los dos roles ---

    public static function rutasDeConsulta(): array
    {
        return [
            'categorías' => ['/categorias'],
            'productos' => ['/productos'],
        ];
    }

    #[DataProvider('rutasDeConsulta')]
    public function test_los_dos_roles_consultan_el_catalogo(string $ruta): void
    {
        foreach ([$this->admin(), $this->vendedor()] as $usuario) {
            $this->actingAs($usuario)->getJson($ruta)->assertOk();
        }
    }

    #[DataProvider('rutasDeConsulta')]
    public function test_consultar_el_catalogo_sin_sesion_rechaza(string $ruta): void
    {
        $this->getJson($ruta)
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    // --- Escritura: solo el administrador ---

    public function test_el_administrador_crea_una_categoria(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/categorias', ['nombre' => 'Abarrotes'])
            ->assertStatus(201);

        $this->assertDatabaseHas('categorias', ['nombre' => 'Abarrotes']);
    }

    public function test_el_vendedor_no_puede_crear_una_categoria(): void
    {
        $this->actingAs($this->vendedor())
            ->postJson('/categorias', ['nombre' => 'Abarrotes'])
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_el_administrador_crea_un_producto(): void
    {
        $categoria = Categoria::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/productos', $this->producto($categoria))
            ->assertStatus(201)
            ->assertJsonPath('codigo', 'ARR-001');

        $this->assertDatabaseHas('productos', ['codigo' => 'ARR-001']);
    }

    public function test_el_vendedor_no_puede_crear_un_producto(): void
    {
        $categoria = Categoria::factory()->create();

        $this->actingAs($this->vendedor())
            ->postJson('/productos', $this->producto($categoria))
            ->assertStatus(403);

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_el_vendedor_no_puede_editar_un_producto(): void
    {
        $producto = Producto::factory()->create();

        $this->actingAs($this->vendedor())
            ->patchJson("/productos/{$producto->id}", ['nombre' => 'Otro nombre'])
            ->assertStatus(403);
    }

    // --- Contrato de la respuesta ---

    public function test_el_listado_de_productos_viene_paginado_de_veinte(): void
    {
        Producto::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->getJson('/productos')
            ->assertOk()
            ->assertJsonPath('por_pagina', 20)
            ->assertJsonPath('total', 3);
    }

    /**
     * El contrato declara que `stockDisponible` está ausente hasta S-04-B: no
     * hay lotes de los que calcularlo. Un cero sería indistinguible de "sin
     * existencias" y la pantalla de catálogo lo mostraría como tal.
     */
    public function test_el_listado_no_trae_stock_disponible_todavia(): void
    {
        Producto::factory()->create();

        $respuesta = $this->actingAs($this->admin())->getJson('/productos');

        $this->assertArrayNotHasKey('stockDisponible', $respuesta->json('datos.0'));
        $this->assertArrayNotHasKey('stock_disponible', $respuesta->json('datos.0'));
    }

    public function test_los_filtros_del_listado_funcionan_por_http(): void
    {
        $categoria = Categoria::factory()->create();
        Producto::factory()->create(['codigo' => 'ARR-001', 'nombre' => 'Arroz', 'categoria_id' => $categoria->id]);
        Producto::factory()->create(['codigo' => 'AZU-001', 'nombre' => 'Azúcar']);

        $this->actingAs($this->admin())
            ->getJson('/productos?buscar=ARR')
            ->assertOk()
            ->assertJsonPath('total', 1);

        $this->actingAs($this->admin())
            ->getJson('/productos?categoriaId='.$categoria->id)
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_ver_un_producto_inexistente_devuelve_no_encontrado(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/productos/9999')
            ->assertStatus(404)
            ->assertJsonPath('error.codigo', 'RECURSO_NO_ENCONTRADO');
    }

    // --- Errores del contrato, con su código ---

    public function test_el_precio_mayor_invalido_devuelve_su_propio_codigo(): void
    {
        $categoria = Categoria::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/productos', array_merge($this->producto($categoria), [
                'precioMenor' => '5.0000',
                'precioMayor' => '9.0000',
            ]))
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'PRODUCTO_PRECIO_MAYOR_INVALIDO');
    }

    public function test_un_codigo_repetido_devuelve_conflicto(): void
    {
        $categoria = Categoria::factory()->create();
        Producto::factory()->create(['codigo' => 'ARR-001']);

        $this->actingAs($this->admin())
            ->postJson('/productos', $this->producto($categoria))
            ->assertStatus(409)
            ->assertJsonPath('error.codigo', 'PRODUCTO_CODIGO_DUPLICADO');
    }

    /** RNF-010: el separador de miles se rechaza, no se interpreta. */
    public function test_un_precio_con_separador_de_miles_se_rechaza(): void
    {
        $categoria = Categoria::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/productos', array_merge($this->producto($categoria), ['precioMenor' => '1,000.50']))
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'CAMPO_FORMATO_INVALIDO');

        $this->assertDatabaseCount('productos', 0);
    }

    public function test_el_codigo_no_se_puede_cambiar_por_http(): void
    {
        $producto = Producto::factory()->create(['codigo' => 'ARR-001']);

        $this->actingAs($this->admin())
            ->patchJson("/productos/{$producto->id}", ['codigo' => 'OTRO-1'])
            ->assertStatus(422);

        $this->assertSame('ARR-001', $producto->refresh()->codigo);
    }

    /**
     * RNF-010 y RNF-012, cada uno en su lugar.
     *
     * El backend guarda el texto tal como llegó: "limpiarlo" acá sería
     * corregir la entrada en silencio, que es justo lo que RNF-010 prohíbe, y
     * dejaría al catálogo con nombres distintos de los que alguien escribió.
     *
     * La codificación que previene XSS ocurre donde el dato se renderiza, en
     * la vista Blade — es de S-02-F, no de este sprint. Lo que sí corresponde
     * comprobar acá es que la respuesta se declara como JSON, para que ningún
     * navegador la interprete como HTML.
     */
    public function test_un_nombre_con_payload_se_guarda_literal_y_se_devuelve_como_json(): void
    {
        $categoria = Categoria::factory()->create();
        $payload = '<script>alert(1)</script> Arroz';

        $respuesta = $this->actingAs($this->admin())
            ->postJson('/productos', array_merge($this->producto($categoria), ['nombre' => $payload]));

        $respuesta->assertStatus(201)->assertJsonPath('nombre', $payload);
        $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));
        $this->assertDatabaseHas('productos', ['nombre' => $payload]);
    }
}
