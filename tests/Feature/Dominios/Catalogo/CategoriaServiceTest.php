<?php

namespace Tests\Feature\Dominios\Catalogo;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Servicios\CategoriaService;
use App\Dominios\Catalogo\Servicios\ProductoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class CategoriaServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoriaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new CategoriaService;
    }

    public function test_crea_una_categoria_recortando_espacios(): void
    {
        $categoria = $this->servicio->crear(DatosDeCatalogo::desde([
            'nombre' => '  Abarrotes  ',
            'descripcion' => '  Productos secos  ',
        ]));

        $this->assertSame('Abarrotes', $categoria->nombre);
        $this->assertSame('Productos secos', $categoria->descripcion);
        $this->assertTrue($categoria->activo);
    }

    public function test_la_descripcion_es_opcional(): void
    {
        $this->assertNull($this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']))->descripcion);
    }

    public static function nombresQueSonElMismo(): array
    {
        return [
            'idéntico' => ['Abarrotes'],
            'en mayúsculas' => ['ABARROTES'],
            'en minúsculas' => ['abarrotes'],
            'con espacios alrededor' => ['  Abarrotes  '],
        ];
    }

    /**
     * Para quien usa el catálogo, "Abarrotes" y "ABARROTES" son la misma
     * categoría: permitir las dos deja duplicados que solo se notan al leer la
     * lista.
     */
    #[DataProvider('nombresQueSonElMismo')]
    public function test_rechaza_un_nombre_ya_usado(string $nombre): void
    {
        $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));

        $this->assertRechaza(
            fn () => $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => $nombre])),
            CodigoDeError::CATEGORIA_NOMBRE_DUPLICADO
        );

        $this->assertSame(1, Categoria::query()->count());
    }

    /**
     * El error nombra su campo porque la pantalla muestra el mensaje al lado
     * de él. Y usa un código propio de la categoría: `DOCUMENTO_DUPLICADO`
     * está reservado al documento de un cliente o proveedor, y el mismo código
     * significando dos cosas obligaría a la pantalla a saber en qué servicio
     * está para traducirlo al campo correcto.
     */
    public function test_el_nombre_repetido_nombra_su_campo(): void
    {
        $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));

        try {
            $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));
            $this->fail('Se aceptó un nombre repetido.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CATEGORIA_NOMBRE_DUPLICADO, $error->codigo);
            $this->assertSame('nombre', $error->detalle['campo'] ?? null);
        }
    }

    public static function nombresInvalidos(): array
    {
        return [
            'ausente' => [[]],
            'vacío' => [['nombre' => '']],
            'solo espacios' => [['nombre' => '   ']],
            'de un carácter' => [['nombre' => 'A']],
            'de más de ochenta' => [['nombre' => str_repeat('A', 81)]],
        ];
    }

    /** @param  array<string, mixed>  $campos */
    #[DataProvider('nombresInvalidos')]
    public function test_rechaza_un_nombre_invalido(array $campos): void
    {
        try {
            $this->servicio->crear(DatosDeCatalogo::desde($campos));
            $this->fail('Se aceptó un nombre que el contrato rechaza.');
        } catch (ErrorDeDominio $error) {
            $this->assertContains($error->codigo, [CodigoDeError::CAMPO_REQUERIDO, CodigoDeError::CAMPO_FUERA_DE_RANGO]);
        }
    }

    public function test_rechaza_una_descripcion_demasiado_larga(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear(DatosDeCatalogo::desde([
                'nombre' => 'Abarrotes',
                'descripcion' => str_repeat('x', 256),
            ])),
            CodigoDeError::CAMPO_FUERA_DE_RANGO
        );
    }

    public function test_editar_conservando_su_propio_nombre_no_es_un_duplicado(): void
    {
        $categoria = $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));

        $actualizada = $this->servicio->actualizar($categoria->id, DatosDeCatalogo::desde([
            'nombre' => 'Abarrotes',
            'descripcion' => 'Nueva descripción',
        ]));

        $this->assertSame('Nueva descripción', $actualizada->descripcion);
    }

    public function test_editar_una_categoria_inexistente_no_la_encuentra(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->actualizar(9999, DatosDeCatalogo::desde(['nombre' => 'Otra'])),
            CodigoDeError::RECURSO_NO_ENCONTRADO
        );
    }

    /**
     * RF-003: desactivar una categoría no toca a los productos que ya la
     * tienen. Solo deja de ofrecerse para los nuevos.
     */
    public function test_desactivarla_no_afecta_a_los_productos_ya_asociados(): void
    {
        $categoria = $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));
        $productos = new ProductoService(new AuditoriaService);

        $producto = $productos->crear(DatosDeCatalogo::desde([
            'codigo' => 'ARR-001',
            'nombre' => 'Arroz extra',
            'categoria_id' => $categoria->id,
            'unidad_medida' => 'KGM',
            'precio_menor' => '5.5000',
            'precio_mayor' => '4.8000',
        ]));

        $this->servicio->actualizar($categoria->id, DatosDeCatalogo::desde(['activo' => false]));

        $producto->refresh();
        $this->assertTrue($producto->activo, 'El producto sigue operativo.');
        $this->assertSame($categoria->id, $producto->categoria_id, 'Y conserva su categoría.');
        $this->assertCount(1, $productos->listar()->items());
    }

    public function test_el_listado_omite_las_inactivas_salvo_que_se_pidan(): void
    {
        $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Abarrotes']));
        $inactiva = $this->servicio->crear(DatosDeCatalogo::desde(['nombre' => 'Descontinuados']));
        $this->servicio->actualizar($inactiva->id, DatosDeCatalogo::desde(['activo' => false]));

        $this->assertCount(1, $this->servicio->listar());
        $this->assertCount(2, $this->servicio->listar(incluirInactivas: true));
    }

    private function assertRechaza(callable $operacion, CodigoDeError $esperado): void
    {
        try {
            $operacion();
            $this->fail("Se aceptó algo que el contrato rechaza con {$esperado->value}.");
        } catch (ErrorDeDominio $error) {
            $this->assertSame($esperado, $error->codigo, "Se esperaba {$esperado->value} y llegó {$error->codigo->value}.");
        }
    }
}
