<?php

namespace Tests\Feature\Dominios\Catalogo;

use App\Compartido\Auditoria\AuditoriaService;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Catalogo\Datos\DatosDeCatalogo;
use App\Dominios\Catalogo\Modelos\Categoria;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Catalogo\Servicios\ProductoService;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProductoServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductoService $servicio;

    private Categoria $categoria;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ProductoService(new AuditoriaService);
        $this->categoria = Categoria::factory()->create();
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = []): DatosDeCatalogo
    {
        return DatosDeCatalogo::desde(array_merge([
            'codigo' => 'ARR-001',
            'nombre' => 'Arroz extra',
            'categoria_id' => $this->categoria->id,
            'unidad_medida' => 'KGM',
            'precio_menor' => '5.5000',
            'precio_mayor' => '4.8000',
        ], $cambios));
    }

    // --- Alta (RF-004) ---

    public function test_crea_un_producto_normalizando_codigo_y_nombre(): void
    {
        $producto = $this->servicio->crear($this->datos([
            'codigo' => '  arr-001  ',
            'nombre' => '  Arroz   extra  ',
        ]));

        $this->assertSame('ARR-001', $producto->codigo);
        $this->assertSame('Arroz extra', $producto->nombre);
        $this->assertSame('5.5000', $producto->precio_menor);
    }

    public function test_el_stock_minimo_es_opcional_y_arranca_en_cero(): void
    {
        $this->assertSame('0.000', $this->servicio->crear($this->datos())->stock_minimo);
    }

    public function test_rechaza_un_codigo_ya_usado(): void
    {
        $this->servicio->crear($this->datos());

        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['codigo' => 'arr-001'])),
            CodigoDeError::PRODUCTO_CODIGO_DUPLICADO
        );
    }

    public function test_rechaza_una_categoria_inexistente(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['categoria_id' => 9999])),
            CodigoDeError::RECURSO_NO_ENCONTRADO
        );
    }

    /** RF-003: una categoría desactivada no se ofrece al crear productos nuevos. */
    public function test_rechaza_una_categoria_desactivada(): void
    {
        $inactiva = Categoria::factory()->inactiva()->create();

        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['categoria_id' => $inactiva->id])),
            CodigoDeError::RECURSO_NO_ENCONTRADO
        );
    }

    public function test_rechaza_una_unidad_fuera_del_catalogo_de_sunat(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['unidad_medida' => 'XYZ'])),
            CodigoDeError::CAMPO_FORMATO_INVALIDO
        );
    }

    public function test_acepta_la_unidad_en_minusculas_y_la_normaliza(): void
    {
        $this->assertSame('KGM', $this->servicio->crear($this->datos(['unidad_medida' => 'kgm']))->unidad_medida);
    }

    // --- Precios: la clase de equivalencia completa (RNF-010) ---

    public static function preciosInvalidos(): array
    {
        return [
            'separador de miles' => ['1,000.50'],
            'cinco decimales' => ['10.00001'],
            'notación científica' => ['1e3'],
            'texto' => ['diez'],
            'espacios alrededor' => [' 10.00 '],
            'signo más' => ['+10.00'],
            'coma decimal' => ['10,50'],
            'negativo' => ['-10.00'],
        ];
    }

    /**
     * El contrato dice que un precio mal escrito se rechaza y **no se corrige
     * en silencio**: aceptar "1,000.50" como mil o como uno cambia el precio
     * de venta según cómo lo interprete quien lo lea.
     */
    #[DataProvider('preciosInvalidos')]
    public function test_rechaza_un_precio_mal_formado(string $precio): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['precio_menor' => $precio, 'precio_mayor' => '1.0000'])),
            CodigoDeError::CAMPO_FORMATO_INVALIDO
        );

        $this->assertSame(0, Producto::query()->count(), 'No debe quedar nada guardado.');
    }

    public static function preciosValidos(): array
    {
        return [
            'entero como cadena' => ['10', '10'],
            'entero de PHP' => [10, '10'],
            'un decimal' => ['10.5', '10.5'],
            'cuatro decimales' => ['10.5000', '10.5000'],
            'flotante de PHP' => [10.5, '10.5'],
        ];
    }

    #[DataProvider('preciosValidos')]
    public function test_acepta_las_formas_validas_de_un_precio(mixed $entrada, string $guardadoComo): void
    {
        $producto = $this->servicio->crear($this->datos([
            'precio_menor' => $entrada,
            'precio_mayor' => '1.0000',
        ]));

        $this->assertSame(0, bccomp($producto->precio_menor, $guardadoComo, 4));
    }

    /** Un precio vacío no es un formato inválido: es un campo que falta. */
    public function test_rechaza_un_precio_vacio_como_campo_requerido(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['precio_menor' => ''])),
            CodigoDeError::CAMPO_REQUERIDO
        );
    }

    public function test_rechaza_un_precio_en_cero(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['precio_menor' => '0', 'precio_mayor' => '0'])),
            CodigoDeError::CAMPO_FUERA_DE_RANGO
        );
    }

    /** RF-004: la regla aprobada por el usuario. */
    public function test_rechaza_un_precio_mayor_superior_al_menor(): void
    {
        $this->assertRechaza(
            fn () => $this->servicio->crear($this->datos(['precio_menor' => '10.0000', 'precio_mayor' => '10.0001'])),
            CodigoDeError::PRODUCTO_PRECIO_MAYOR_INVALIDO
        );
    }

    public function test_acepta_que_los_dos_precios_sean_iguales(): void
    {
        $this->assertSame(
            0,
            bccomp($this->servicio->crear($this->datos([
                'precio_menor' => '9.9999',
                'precio_mayor' => '9.9999',
            ]))->precio_mayor, '9.9999', 4)
        );
    }

    // --- Edición ---

    public function test_el_codigo_no_se_puede_cambiar(): void
    {
        $producto = $this->servicio->crear($this->datos());

        $this->assertRechaza(
            fn () => $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['codigo' => 'OTRO-1'])),
            CodigoDeError::CAMPO_FORMATO_INVALIDO
        );

        $this->assertSame('ARR-001', $producto->refresh()->codigo);
    }

    public function test_reenviar_el_mismo_codigo_no_es_un_cambio(): void
    {
        $producto = $this->servicio->crear($this->datos());

        $actualizado = $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde([
            'codigo' => 'ARR-001',
            'nombre' => 'Arroz superior',
        ]));

        $this->assertSame('Arroz superior', $actualizado->nombre);
    }

    /** Cambiar un solo precio también puede romper la regla. */
    public function test_bajar_solo_el_precio_menor_por_debajo_del_mayor_se_rechaza(): void
    {
        $producto = $this->servicio->crear($this->datos(['precio_menor' => '10.0000', 'precio_mayor' => '9.0000']));

        $this->assertRechaza(
            fn () => $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['precio_menor' => '8.0000'])),
            CodigoDeError::PRODUCTO_PRECIO_MAYOR_INVALIDO
        );
    }

    public function test_desactivar_un_producto_no_lo_borra(): void
    {
        $producto = $this->servicio->crear($this->datos());

        $this->assertFalse($this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['activo' => '0']))->activo);
        $this->assertDatabaseHas('productos', ['codigo' => 'ARR-001']);
    }

    // --- Listado ---

    public function test_lista_paginado_de_veinte_ordenado_por_nombre(): void
    {
        Producto::factory()->count(25)->create(['categoria_id' => $this->categoria->id]);

        $pagina = $this->servicio->listar();

        $this->assertSame(20, $pagina->perPage());
        $this->assertSame(25, $pagina->total());

        $nombres = array_map(fn (Producto $p) => $p->nombre, $pagina->items());
        $ordenados = $nombres;
        sort($ordenados, SORT_STRING);
        $this->assertSame($ordenados, $nombres);
    }

    public function test_la_busqueda_coincide_por_codigo_y_por_nombre(): void
    {
        $this->servicio->crear($this->datos(['codigo' => 'ARR-001', 'nombre' => 'Arroz extra']));
        $this->servicio->crear($this->datos(['codigo' => 'AZU-001', 'nombre' => 'Azúcar rubia']));

        $this->assertCount(1, $this->servicio->listar('ARR')->items());
        $this->assertCount(1, $this->servicio->listar('azúcar')->items());
        $this->assertCount(2, $this->servicio->listar('001')->items());
    }

    public function test_por_defecto_no_lista_los_inactivos(): void
    {
        $producto = $this->servicio->crear($this->datos());
        $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['activo' => false]));

        $this->assertCount(0, $this->servicio->listar()->items());
        $this->assertCount(1, $this->servicio->listar(soloActivos: false)->items());
    }

    /** RNF-011. */
    public function test_la_busqueda_no_es_vulnerable_a_inyeccion(): void
    {
        $this->servicio->crear($this->datos());

        $this->assertCount(0, $this->servicio->listar("' or 1=1 --")->items());
        $this->assertSame(1, Producto::query()->count());
    }

    // --- Auditoría (UT-04, RNF-004) ---

    public function test_cambiar_un_precio_deja_rastro_con_el_valor_anterior_y_su_responsable(): void
    {
        $actor = Usuario::factory()->administrador()->create();
        $producto = $this->servicio->crear($this->datos(['precio_menor' => '5.5000']));

        $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['precio_menor' => '6.0000']), $actor);

        $this->assertDatabaseCount('auditorias', 1);

        $fila = \DB::table('auditorias')->first();
        $this->assertSame('Producto', $fila->entidad);
        $this->assertSame($producto->id, (int) $fila->entidad_id);
        $this->assertSame('actualizar', $fila->accion);
        $this->assertSame($actor->id, (int) $fila->usuario_id);
        $this->assertSame(0, bccomp(json_decode($fila->valores_anteriores, true)['precio_menor'], '5.5000', 4));
        $this->assertSame(0, bccomp(json_decode($fila->valores_nuevos, true)['precio_menor'], '6.0000', 4));
    }

    public function test_cambiar_algo_que_no_es_precio_no_deja_rastro(): void
    {
        $producto = $this->servicio->crear($this->datos());

        $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['nombre' => 'Arroz superior']));

        $this->assertDatabaseCount('auditorias', 0);
    }

    /** Guardar el mismo precio no es un cambio: la bitácora no debe llenarse de ruido. */
    public function test_guardar_el_mismo_precio_no_deja_rastro(): void
    {
        $producto = $this->servicio->crear($this->datos(['precio_menor' => '5.5000']));

        $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde(['precio_menor' => '5.50']));

        $this->assertDatabaseCount('auditorias', 0);
    }

    /** RNF-014: la bitácora lleva solo los campos declarados auditables. */
    public function test_la_auditoria_no_registra_campos_que_no_sean_precios(): void
    {
        $actor = Usuario::factory()->administrador()->create();
        $producto = $this->servicio->crear($this->datos());

        $this->servicio->actualizar($producto->id, DatosDeCatalogo::desde([
            'precio_menor' => '7.0000',
            'nombre' => 'Arroz superior',
        ]), $actor);

        $registrados = array_keys(json_decode(\DB::table('auditorias')->value('valores_nuevos'), true));
        $this->assertSame(['precio_menor'], $registrados);
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
