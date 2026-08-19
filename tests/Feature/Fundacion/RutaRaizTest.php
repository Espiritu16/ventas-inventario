<?php

namespace Tests\Feature\Fundacion;

use Tests\TestCase;

/**
 * La ruta raíz es lo único que S-00 sirve: confirma que el layout base
 * renderiza y que los assets compilados por Vite se referencian.
 */
final class RutaRaizTest extends TestCase
{
    public function test_la_raiz_responde_y_renderiza_el_layout_base(): void
    {
        $respuesta = $this->get('/');

        $respuesta->assertOk();
        $respuesta->assertSee(config('app.name'), false);
        $respuesta->assertSee('<html lang="es">', false);
    }

    public function test_la_raiz_referencia_los_assets_compilados(): void
    {
        $this->assertFileExists(public_path('build/manifest.json'), 'Falta compilar los assets: pnpm build');

        $this->get('/')->assertSee('/build/assets/', false);
    }
}
