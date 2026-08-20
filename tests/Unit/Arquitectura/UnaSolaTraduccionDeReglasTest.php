<?php

namespace Tests\Unit\Arquitectura;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * La traducción de una regla de validación a un código de la taxonomía vive en
 * un solo sitio.
 *
 * `UsuarioService` llegó a tener su propia copia, escrita antes de que
 * existiera la compartida, y las dos divergieron sin que nada avisara: la
 * compartida trataba `between` y la copia no. Es el mismo patrón de dos
 * fuentes mantenidas a mano que el proyecto ya viene registrando, y acá —a
 * diferencia de cuando una de las fuentes es un documento que la gente lee— la
 * duplicación se puede eliminar del todo.
 *
 * Una copia nueva no se nota leyendo el código: funciona igual el día que se
 * escribe. Se nota cuando una de las dos cambia.
 */
final class UnaSolaTraduccionDeReglasTest extends TestCase
{
    private const UNICA_FUENTE = 'Compartido/Errores/ValidadorDeDominio.php';

    public function test_solo_el_validador_compartido_traduce_reglas_a_codigos(): void
    {
        $copias = self::archivosQueTraducenReglas(dirname(__DIR__, 3).'/app');

        $this->assertSame(
            [self::UNICA_FUENTE],
            $copias,
            'Hay más de una traducción de reglas a códigos: '.implode(', ', $copias)
        );
    }

    public function test_reconoce_una_copia_puesta_en_otro_archivo(): void
    {
        $app = sys_get_temp_dir().'/traduccion-'.uniqid();
        mkdir($app.'/Dominios/Usuarios/Servicios', 0777, true);

        file_put_contents($app.'/Dominios/Usuarios/Servicios/UsuarioService.php', <<<'CLASE'
            <?php

            namespace App\Dominios\Usuarios\Servicios;

            class UsuarioService
            {
                private function codigoSegunRegla(array $reglasFalladas): CodigoDeError
                {
                    return match ($reglasFalladas[0]) {
                        'Required' => CodigoDeError::CAMPO_REQUERIDO,
                        default => CodigoDeError::CAMPO_FORMATO_INVALIDO,
                    };
                }
            }
            CLASE);

        try {
            $this->assertSame(
                ['Dominios/Usuarios/Servicios/UsuarioService.php'],
                self::archivosQueTraducenReglas($app)
            );
        } finally {
            self::borrarRecursivo($app);
        }
    }

    /**
     * Un archivo traduce reglas si nombra las reglas de Laravel junto a los
     * códigos de la taxonomía. Se mira el texto, no la clase cargada: una
     * copia puede estar en cualquier namespace.
     *
     * @return list<string>
     */
    private static function archivosQueTraducenReglas(string $rutaApp): array
    {
        $rutaApp = rtrim($rutaApp, DIRECTORY_SEPARATOR);
        $encontrados = [];

        $archivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rutaApp, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $archivo */
        foreach ($archivos as $archivo) {
            if ($archivo->getExtension() !== 'php') {
                continue;
            }

            $contenido = (string) file_get_contents($archivo->getPathname());

            if (preg_match("/'Required'\s*=>\s*CodigoDeError::/", $contenido) !== 1) {
                continue;
            }

            $encontrados[] = str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($archivo->getPathname(), strlen($rutaApp) + 1)
            );
        }

        sort($encontrados);

        return $encontrados;
    }

    private static function borrarRecursivo(string $ruta): void
    {
        foreach (glob($ruta.'/*') ?: [] as $hijo) {
            is_dir($hijo) ? self::borrarRecursivo($hijo) : unlink($hijo);
        }

        rmdir($ruta);
    }
}
