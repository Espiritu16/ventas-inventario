<?php

namespace Tests\Feature\Autorizacion;

use App\Compartido\Autorizacion\MatrizDePermisos;
use App\Dominios\Usuarios\Modelos\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Permisos de las pantallas que construye el frente de interfaz.
 *
 * Se declaran acá porque la matriz es del backend, pero las rutas viven en
 * `routes/web.php` y todavía no existen: por eso se comprueba la declaración y
 * no la respuesta HTTP. Sin estas filas, la pantalla de acceso exigiría sesión
 * para poder obtener una sesión, y el tablero rechazaría al administrador.
 */
final class PantallasDeclaradasTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_pantalla_de_acceso_se_sirve_sin_sesion(): void
    {
        $this->assertTrue(MatrizDePermisos::seSirveSinSesion('GET /login'));
    }

    public function test_la_pantalla_de_acceso_no_abre_otras_rutas_de_login(): void
    {
        $this->assertFalse(MatrizDePermisos::seSirveSinSesion('PATCH /login'));
        $this->assertFalse(MatrizDePermisos::seSirveSinSesion('GET /login/algo'));
    }

    public static function rolesDelPanel(): array
    {
        return [
            'administrador' => [Usuario::ROL_ADMINISTRADOR],
            'vendedor' => [Usuario::ROL_VENDEDOR],
        ];
    }

    #[DataProvider('rolesDelPanel')]
    public function test_el_tablero_admite_a_los_dos_roles(string $rol): void
    {
        $usuario = Usuario::factory()->create(['rol' => $rol]);

        $this->assertTrue(MatrizDePermisos::tieneReglaDeclarada('GET /panel'));
        $this->assertTrue(MatrizDePermisos::permiteA('GET /panel', $usuario));
    }

    public function test_el_tablero_no_se_sirve_sin_sesion(): void
    {
        $this->assertFalse(MatrizDePermisos::seSirveSinSesion('GET /panel'));
    }

    /**
     * Las tres rutas de gestión de usuarios siguen siendo del administrador,
     * aunque ahora sean pantallas y no endpoints JSON: cambió quién las sirve,
     * no quién puede verlas.
     */
    public function test_la_gestion_de_usuarios_sigue_siendo_solo_del_administrador(): void
    {
        $admin = Usuario::factory()->administrador()->create();
        $vendedor = Usuario::factory()->create();

        foreach (['GET /usuarios', 'POST /usuarios', 'PATCH /usuarios/{id}'] as $ruta) {
            $this->assertTrue(MatrizDePermisos::permiteA($ruta, $admin), $ruta);
            $this->assertFalse(MatrizDePermisos::permiteA($ruta, $vendedor), $ruta);
            $this->assertFalse(MatrizDePermisos::seSirveSinSesion($ruta), $ruta);
        }
    }
}
