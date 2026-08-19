<?php

namespace Tests\Unit\Arquitectura;

use PHPUnit\Framework\TestCase;
use Tests\Soporte\DetectorDeClasesDeDominio;

/**
 * Sostiene la organización domain-first de ADR-0005: todo el código de un
 * dominio vive en `app/Dominios/<Dominio>/`, y lo compartido entre dominios en
 * `app/Compartido/`. Nada de eso puede aparecer suelto en `app/`.
 */
final class EstructuraDeDominiosTest extends TestCase
{
    public function test_no_hay_clases_de_dominio_fuera_de_dominios_ni_compartido(): void
    {
        $malUbicadas = DetectorDeClasesDeDominio::malUbicadas(dirname(__DIR__, 3).'/app');

        $this->assertSame(
            [],
            $malUbicadas,
            'Estas clases de dominio están fuera de app/Dominios/ y app/Compartido/: '
            .implode(', ', $malUbicadas)
        );
    }

    public function test_rechaza_un_modelo_de_dominio_ubicado_en_app_models(): void
    {
        $app = sys_get_temp_dir().'/arquitectura-'.uniqid();
        mkdir($app.'/Models', 0777, true);
        mkdir($app.'/Dominios/Catalogo/Modelos', 0777, true);

        file_put_contents($app.'/Models/Producto.php', <<<'CLASE'
            <?php

            namespace App\Models;

            use Illuminate\Database\Eloquent\Model;

            class Producto extends Model {}
            CLASE);

        file_put_contents($app.'/Dominios/Catalogo/Modelos/Categoria.php', <<<'CLASE'
            <?php

            namespace App\Dominios\Catalogo\Modelos;

            use Illuminate\Database\Eloquent\Model;

            class Categoria extends Model {}
            CLASE);

        try {
            $this->assertSame(['Models/Producto.php'], DetectorDeClasesDeDominio::malUbicadas($app));
        } finally {
            self::borrarRecursivo($app);
        }
    }

    public function test_rechaza_un_servicio_de_dominio_fuera_de_su_dominio(): void
    {
        $app = sys_get_temp_dir().'/arquitectura-'.uniqid();
        mkdir($app.'/Servicios', 0777, true);

        file_put_contents($app.'/Servicios/VentaService.php', <<<'CLASE'
            <?php

            namespace App\Servicios;

            class VentaService {}
            CLASE);

        try {
            $this->assertSame(['Servicios/VentaService.php'], DetectorDeClasesDeDominio::malUbicadas($app));
        } finally {
            self::borrarRecursivo($app);
        }
    }

    /**
     * El contrato de servicios de dominio usa el sufijo en inglés, pero nada
     * impide que alguien escriba `VentaServicio` o `ProductoRepositorio`. El
     * guardián tiene que verlos igual: si solo reconociera una de las dos
     * formas, quedaría ciego justo cuando aparezcan los primeros servicios.
     */
    public function test_rechaza_tambien_los_sufijos_escritos_en_espanol(): void
    {
        $app = sys_get_temp_dir().'/arquitectura-'.uniqid();
        mkdir($app.'/Servicios', 0777, true);
        mkdir($app.'/Repositorios', 0777, true);

        file_put_contents($app.'/Servicios/VentaServicio.php', <<<'CLASE'
            <?php

            namespace App\Servicios;

            class VentaServicio {}
            CLASE);

        file_put_contents($app.'/Repositorios/ProductoRepositorio.php', <<<'CLASE'
            <?php

            namespace App\Repositorios;

            class ProductoRepositorio {}
            CLASE);

        try {
            $this->assertSame(
                ['Repositorios/ProductoRepositorio.php', 'Servicios/VentaServicio.php'],
                DetectorDeClasesDeDominio::malUbicadas($app)
            );
        } finally {
            self::borrarRecursivo($app);
        }
    }

    private static function borrarRecursivo(string $ruta): void
    {
        foreach (glob($ruta.'/*') ?: [] as $hijo) {
            is_dir($hijo) ? self::borrarRecursivo($hijo) : unlink($hijo);
        }

        rmdir($ruta);
    }
}
