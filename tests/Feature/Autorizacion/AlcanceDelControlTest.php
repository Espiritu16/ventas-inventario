<?php

namespace Tests\Feature\Autorizacion;

use App\Dominios\Usuarios\Modelos\Usuario;
use App\Http\Middleware\Autorizar;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El control no puede depender de en qué archivo se declaró una ruta ni de si
 * alguien recordó ponerle un grupo.
 *
 * Esto no es teórico: cuando el middleware estaba en el grupo `web`,
 * `GET /up` y `GET /storage/{path}` respondían por quedar fuera de ese grupo,
 * no por estar declaradas. Las pruebas de comportamiento pasaban igual, por
 * la razón equivocada.
 */
final class AlcanceDelControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_control_es_global_y_no_depende_de_ningun_grupo(): void
    {
        $this->assertContains(
            Autorizar::class,
            app(Kernel::class)->getGlobalMiddleware(),
            'Si el control vuelve a un grupo, las rutas que no lo declaren quedan sin control.'
        );
    }

    /**
     * Ninguna ruta viva puede quedar fuera del alcance, venga de donde venga:
     * de routes/backend.php, de routes/web.php, del framework o de un paquete.
     */
    public function test_ninguna_ruta_registrada_escapa_al_control(): void
    {
        $global = app(Kernel::class)->getGlobalMiddleware();

        $this->assertContains(Autorizar::class, $global);

        // El alcance se comprueba además por comportamiento, en rutas de los
        // cuatro orígenes.
        $admin = Usuario::factory()->administrador()->create();

        $this->get('/')->assertOk();                                    // routes/web.php
        $this->postJson('/login', [])->assertStatus(422);               // routes/backend.php
        $this->get('/up')->assertOk();                                  // framework
        $this->actingAs($admin)->getJson('/storage/x.txt')->assertStatus(403); // framework, no declarada
    }

    /**
     * `GET /storage/{path}` está declarada como NO autorizada. Que se rechace
     * tiene que verse en el código de la taxonomía, no en un status que el
     * framework devolvería igual por su cuenta: antes daba 403 porque el
     * archivo no existía, y parecía lo mismo.
     */
    public function test_la_ruta_de_almacenamiento_se_rechaza_con_nuestro_codigo(): void
    {
        $this->getJson('/storage/x.txt')
            ->assertStatus(401)
            ->assertJsonPath('error.codigo', 'NO_AUTENTICADO');

        $this->actingAs(Usuario::factory()->administrador()->create())
            ->getJson('/storage/x.txt')
            ->assertStatus(403)
            ->assertJsonPath('error.codigo', 'NO_AUTORIZADO');
    }

    /** El health check responde por estar declarado, y sí pasa por el control. */
    public function test_el_health_check_pasa_por_el_control_y_responde(): void
    {
        $this->get('/up')->assertOk();

        $this->actingAs(Usuario::factory()->create())->get('/up')->assertOk();
    }
}
