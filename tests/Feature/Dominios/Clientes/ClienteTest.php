<?php

namespace Tests\Feature\Dominios\Clientes;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Documentos\TipoDeDocumento;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Clientes\Servicios\ClienteService;
use App\Dominios\Usuarios\Modelos\Usuario;
use Database\Seeders\ClientePublicoGeneralSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ClienteTest extends TestCase
{
    use RefreshDatabase;

    private ClienteService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ClienteService;
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = []): DatosDeEntrada
    {
        return DatosDeEntrada::desde(array_merge([
            'tipo_documento' => TipoDeDocumento::DNI->value,
            'numero_documento' => '12345678',
            'nombre' => 'Ana Quispe',
        ], $cambios));
    }

    // --- Los cuatro tipos se comportan según el contrato (UT-03) ---

    public static function tiposYNumeros(): array
    {
        return [
            'DNI de 8' => ['1', '12345678', '12345678'],
            'carné alfanumérico' => ['4', 'AB123456', 'AB123456'],
            'RUC de 11' => ['6', '20123456789', '20123456789'],
            'sin documento' => ['0', null, null],
        ];
    }

    #[DataProvider('tiposYNumeros')]
    public function test_cada_tipo_de_documento_se_comporta_segun_el_contrato(
        string $tipo,
        ?string $numero,
        ?string $guardado,
    ): void {
        $cliente = $this->servicio->crear($this->datos([
            'tipo_documento' => $tipo,
            'numero_documento' => $numero,
        ]));

        $this->assertSame($tipo, $cliente->tipo_documento);
        $this->assertSame($guardado, $cliente->numero_documento);
    }

    /**
     * Criterio de cierre de UT-01: la caja vende a público general muchas
     * veces al día, y cada venta no puede exigir un cliente distinto.
     */
    public function test_varios_clientes_sin_documento_conviven(): void
    {
        $this->servicio->crear($this->datos(['tipo_documento' => '0', 'numero_documento' => null, 'nombre' => 'Público general']));
        $this->servicio->crear($this->datos(['tipo_documento' => '0', 'numero_documento' => null, 'nombre' => 'Otro sin documento']));

        $this->assertSame(2, Cliente::query()->whereNull('numero_documento')->count());
    }

    public function test_dos_clientes_con_el_mismo_documento_no_conviven(): void
    {
        $this->servicio->crear($this->datos());

        try {
            $this->servicio->crear($this->datos(['nombre' => 'Otra Ana']));
            $this->fail('Se aceptó un documento repetido.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_DUPLICADO, $error->codigo);
        }

        $this->assertSame(1, Cliente::query()->count());
    }

    /** El mismo número con distinto tipo son personas distintas. */
    public function test_el_mismo_numero_con_otro_tipo_no_es_duplicado(): void
    {
        $this->servicio->crear($this->datos(['tipo_documento' => '1', 'numero_documento' => '12345678']));

        $cliente = $this->servicio->crear($this->datos([
            'tipo_documento' => '4',
            'numero_documento' => '12345678',
            'nombre' => 'Otra persona',
        ]));

        $this->assertSame('4', $cliente->tipo_documento);
        $this->assertSame(2, Cliente::query()->count());
    }

    public static function documentosQueNoCorresponden(): array
    {
        return [
            'DNI de 7' => ['1', '1234567'],
            'DNI de 11' => ['1', '12345678901'],
            'DNI con guion' => ['1', '1234-5678'],
            'RUC de 8' => ['6', '12345678'],
            'RUC con letras' => ['6', '2012345678A'],
            'carné de 13' => ['4', 'ABCD123456789'],
            'sin documento con número' => ['0', '12345678'],
        ];
    }

    #[DataProvider('documentosQueNoCorresponden')]
    public function test_rechaza_el_documento_que_no_corresponde_al_tipo(string $tipo, string $numero): void
    {
        try {
            $this->servicio->crear($this->datos(['tipo_documento' => $tipo, 'numero_documento' => $numero]));
            $this->fail("Se aceptó «{$numero}» para el tipo {$tipo}.");
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_INVALIDO, $error->codigo);
        }

        $this->assertSame(0, Cliente::query()->count());
    }

    public function test_un_tipo_que_exige_documento_sin_numero_reclama_el_campo(): void
    {
        try {
            $this->servicio->crear($this->datos(['tipo_documento' => '1', 'numero_documento' => null]));
            $this->fail('Se aceptó un DNI sin número.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_REQUERIDO, $error->codigo);
        }
    }

    public function test_rechaza_un_tipo_de_documento_inexistente(): void
    {
        try {
            $this->servicio->crear($this->datos(['tipo_documento' => '9']));
            $this->fail('Se aceptó un tipo de documento fuera del catálogo.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }
    }

    // --- Edición ---

    public function test_ni_el_tipo_ni_el_numero_se_pueden_cambiar(): void
    {
        $cliente = $this->servicio->crear($this->datos());

        foreach ([['tipo_documento' => '6'], ['numero_documento' => '87654321']] as $cambio) {
            try {
                $this->servicio->actualizar($cliente->id, DatosDeEntrada::desde($cambio));
                $this->fail('Se cambió el documento de un cliente: '.json_encode($cambio));
            } catch (ErrorDeDominio $error) {
                $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
            }
        }

        $cliente->refresh();
        $this->assertSame('1', $cliente->tipo_documento);
        $this->assertSame('12345678', $cliente->numero_documento);
    }

    public function test_se_puede_editar_el_resto_de_los_datos(): void
    {
        $cliente = $this->servicio->crear($this->datos());

        $actualizado = $this->servicio->actualizar($cliente->id, DatosDeEntrada::desde([
            'nombre' => 'Ana Quispe Rojas',
            'direccion' => 'Av. Siempre Viva 123',
        ]));

        $this->assertSame('Ana Quispe Rojas', $actualizado->nombre);
        $this->assertSame('Av. Siempre Viva 123', $actualizado->direccion);
    }

    // --- Listado ---

    public function test_la_busqueda_coincide_por_nombre_y_por_documento(): void
    {
        Cliente::factory()->create(['nombre' => 'Ana Quispe', 'numero_documento' => '11111111']);
        Cliente::factory()->create(['nombre' => 'Luis Rojas', 'numero_documento' => '22222222']);

        $this->assertCount(1, $this->servicio->listar('quispe')->items());
        $this->assertCount(1, $this->servicio->listar('2222')->items());
    }

    // --- Público general (UT-04) ---

    public function test_el_publico_general_existe_tras_sembrar(): void
    {
        $this->seed(ClientePublicoGeneralSeeder::class);

        $cliente = Cliente::query()->where('tipo_documento', '0')->first();

        $this->assertNotNull($cliente);
        $this->assertNull($cliente->numero_documento);
        $this->assertTrue($cliente->activo);
        $this->assertFalse($cliente->tieneDocumento());
    }

    public function test_sembrar_dos_veces_no_duplica_al_publico_general(): void
    {
        $this->seed(ClientePublicoGeneralSeeder::class);
        $this->seed(ClientePublicoGeneralSeeder::class);

        $this->assertSame(1, Cliente::query()->where('tipo_documento', '0')->count());
    }

    // --- Rutas (RNF-013) ---

    public function test_el_vendedor_consulta_y_da_de_alta_clientes_desde_la_caja(): void
    {
        $vendedor = Usuario::factory()->create();

        $this->actingAs($vendedor)->getJson('/clientes')->assertOk();

        $this->actingAs($vendedor)
            ->postJson('/clientes', ['tipoDocumento' => '1', 'numeroDocumento' => '12345678', 'nombre' => 'Ana Quispe'])
            ->assertStatus(201);
    }

    public function test_el_vendedor_no_puede_editar_un_cliente_ya_registrado(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs(Usuario::factory()->create())
            ->patchJson("/clientes/{$cliente->id}", ['nombre' => 'Otro nombre'])
            ->assertStatus(403);
    }

    public function test_sin_sesion_las_rutas_de_clientes_rechazan(): void
    {
        $this->getJson('/clientes')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');
    }

    public function test_un_documento_invalido_por_http_devuelve_documento_invalido(): void
    {
        $this->actingAs(Usuario::factory()->administrador()->create())
            ->postJson('/clientes', ['tipoDocumento' => '1', 'numeroDocumento' => '1234-5678', 'nombre' => 'Ana Quispe'])
            ->assertStatus(422)
            ->assertJsonPath('error.codigo', 'DOCUMENTO_INVALIDO');

        $this->assertSame(0, Cliente::query()->count());
    }
}
