<?php

namespace Tests\Feature\Dominios\Proveedores;

use App\Compartido\Datos\DatosDeEntrada;
use App\Compartido\Errores\CodigoDeError;
use App\Compartido\Errores\ErrorDeDominio;
use App\Dominios\Proveedores\Modelos\Proveedor;
use App\Dominios\Proveedores\Servicios\ProveedorService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ProveedorTest extends TestCase
{
    use RefreshDatabase;

    private ProveedorService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new ProveedorService;
    }

    /** @param  array<string, mixed>  $cambios */
    private function datos(array $cambios = []): DatosDeEntrada
    {
        return DatosDeEntrada::desde(array_merge([
            'numero_documento' => '20123456789',
            'razon_social' => 'Distribuidora del Norte SAC',
        ], $cambios));
    }

    // --- Schema (UT-01) ---

    public function test_la_base_exige_once_digitos_en_el_ruc(): void
    {
        $this->expectException(QueryException::class);

        DB::table('proveedores')->insert([
            'tipo_documento' => '6',
            'numero_documento' => '2012345678',
            'razon_social' => 'X',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_la_base_no_admite_otro_tipo_de_documento(): void
    {
        $this->expectException(QueryException::class);

        DB::table('proveedores')->insert([
            'tipo_documento' => '1',
            'numero_documento' => '20123456789',
            'razon_social' => 'X',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // --- Alta (RF-005) ---

    public function test_crea_un_proveedor_con_su_ruc(): void
    {
        $proveedor = $this->servicio->crear($this->datos());

        $this->assertSame('6', $proveedor->tipo_documento);
        $this->assertSame('20123456789', $proveedor->numero_documento);
        $this->assertTrue($proveedor->activo);
    }

    public function test_normaliza_la_razon_social_y_el_correo(): void
    {
        $proveedor = $this->servicio->crear($this->datos([
            'razon_social' => '  Distribuidora   del  Norte SAC  ',
            'email' => '  VENTAS@Ejemplo.PE ',
        ]));

        $this->assertSame('Distribuidora del Norte SAC', $proveedor->razon_social);
        $this->assertSame('ventas@ejemplo.pe', $proveedor->email);
    }

    public static function rucsInvalidos(): array
    {
        return [
            'de 10 dígitos' => ['2012345678'],
            'de 12 dígitos' => ['201234567890'],
            'con guiones' => ['20-12345678-9'],
            'con espacios internos' => ['201 2345 6789'],
            'con letras' => ['2012345678A'],
            'vacío' => [''],
        ];
    }

    #[DataProvider('rucsInvalidos')]
    public function test_rechaza_un_ruc_que_no_tiene_once_digitos(string $ruc): void
    {
        try {
            $this->servicio->crear($this->datos(['numero_documento' => $ruc]));
            $this->fail("Se aceptó «{$ruc}» como RUC.");
        } catch (ErrorDeDominio $error) {
            $this->assertContains($error->codigo, [CodigoDeError::DOCUMENTO_INVALIDO, CodigoDeError::CAMPO_REQUERIDO]);
        }

        $this->assertSame(0, Proveedor::query()->count());
    }

    /**
     * Un documento repetido y uno mal formado son problemas distintos: a quien
     * escribió un espacio de más hay que decirle que el formato falla, no que
     * el proveedor ya existe, o va a cambiar el número en vez de borrar el
     * espacio.
     */
    public function test_un_ruc_repetido_y_uno_mal_formado_dan_codigos_distintos(): void
    {
        $this->servicio->crear($this->datos());

        try {
            $this->servicio->crear($this->datos(['razon_social' => 'Otra empresa SAC']));
            $this->fail('Se aceptó un RUC repetido.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_DUPLICADO, $error->codigo);
        }

        try {
            $this->servicio->crear($this->datos(['numero_documento' => '2012345678']));
            $this->fail('Se aceptó un RUC mal formado.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::DOCUMENTO_INVALIDO, $error->codigo);
        }
    }

    public static function telefonosInvalidos(): array
    {
        return [
            'con letras' => ['987ABC321'],
            'demasiado corto' => ['12345'],
            'demasiado largo' => ['123456789012345678901'],
        ];
    }

    #[DataProvider('telefonosInvalidos')]
    public function test_rechaza_un_telefono_mal_formado(string $telefono): void
    {
        try {
            $this->servicio->crear($this->datos(['telefono' => $telefono]));
            $this->fail("Se aceptó el teléfono «{$telefono}».");
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }
    }

    public function test_acepta_telefono_con_mas_y_guiones(): void
    {
        $this->assertSame('+51 987-654-321', $this->servicio->crear($this->datos(['telefono' => '+51 987-654-321']))->telefono);
    }

    // --- Edición (RF-005) ---

    public function test_el_documento_no_se_puede_cambiar(): void
    {
        $proveedor = $this->servicio->crear($this->datos());

        try {
            $this->servicio->actualizar($proveedor->id, DatosDeEntrada::desde(['numero_documento' => '20999999999']));
            $this->fail('Se cambió el documento de un proveedor.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::CAMPO_FORMATO_INVALIDO, $error->codigo);
        }

        $this->assertSame('20123456789', $proveedor->refresh()->numero_documento);
    }

    public function test_reenviar_el_mismo_documento_no_es_un_cambio(): void
    {
        $proveedor = $this->servicio->crear($this->datos());

        $actualizado = $this->servicio->actualizar($proveedor->id, DatosDeEntrada::desde([
            'numero_documento' => '20123456789',
            'razon_social' => 'Distribuidora del Sur SAC',
        ]));

        $this->assertSame('Distribuidora del Sur SAC', $actualizado->razon_social);
    }

    public function test_desactivar_un_proveedor_no_lo_borra(): void
    {
        $proveedor = $this->servicio->crear($this->datos());

        $this->assertFalse($this->servicio->actualizar($proveedor->id, DatosDeEntrada::desde(['activo' => '0']))->activo);
        $this->assertDatabaseHas('proveedores', ['numero_documento' => '20123456789']);
    }

    public function test_editar_uno_inexistente_no_lo_encuentra(): void
    {
        try {
            $this->servicio->actualizar(9999, DatosDeEntrada::desde(['razon_social' => 'Quien sea SAC']));
            $this->fail('Se editó un proveedor que no existe.');
        } catch (ErrorDeDominio $error) {
            $this->assertSame(CodigoDeError::RECURSO_NO_ENCONTRADO, $error->codigo);
        }
    }

    // --- Listado ---

    public function test_la_busqueda_coincide_por_razon_social_y_por_documento(): void
    {
        Proveedor::factory()->create(['razon_social' => 'Distribuidora del Norte SAC', 'numero_documento' => '20111111111']);
        Proveedor::factory()->create(['razon_social' => 'Importaciones del Sur EIRL', 'numero_documento' => '20222222222']);

        $this->assertCount(1, $this->servicio->listar('norte')->items());
        $this->assertCount(1, $this->servicio->listar('20222')->items());
    }

    public function test_por_defecto_no_lista_los_inactivos(): void
    {
        Proveedor::factory()->inactivo()->create();

        $this->assertCount(0, $this->servicio->listar()->items());
        $this->assertCount(1, $this->servicio->listar(soloActivos: false)->items());
    }

    // Las pruebas que ejercían estas operaciones por HTTP se retiraron con sus
    // rutas (ADR-0006): los recursos de dominio se sirven como pantallas y la
    // operación la ejecuta este servicio, invocado en el mismo proceso. Quién
    // puede hacer qué lo comprueba el mecanismo de permisos en componentes;
    // lo que este archivo cubre es que la operación haga lo que dice.
}
