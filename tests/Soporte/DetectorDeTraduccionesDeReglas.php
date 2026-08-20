<?php

namespace Tests\Soporte;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Encuentra los archivos que traducen un fallo de validación a un código de la
 * taxonomía.
 *
 * Reconoce la traducción por su vocabulario y no por su sintaxis: da igual que
 * se escriba con `match`, con `if`, con `switch` o como tabla, porque lo que la
 * define es que en el mismo archivo convivan **nombres de reglas** y **códigos
 * genéricos** de la taxonomía. Buscar una forma concreta dejaría pasar al que
 * se escribe la suya, que es como aparecen estas copias.
 *
 * Los nombres de reglas se buscan **capitalizados y entre comillas** porque así
 * es como los devuelve `Validator::failed()`, que es el único punto donde hace
 * falta traducirlos. En minúscula son otra cosa: lo que se escribe al declarar
 * la validación, que hace cualquier servicio y no es una traducción.
 */
final class DetectorDeTraduccionesDeReglas
{
    /** Los que cualquiera podría reasignar por su cuenta. Los que nombran una entidad no se traducen desde una regla. */
    private const CODIGOS_GENERICOS = [
        'CAMPO_REQUERIDO',
        'CAMPO_FORMATO_INVALIDO',
        'CAMPO_FUERA_DE_RANGO',
    ];

    /** Como los nombra `Validator::failed()`. */
    private const NOMBRES_DE_REGLAS = [
        'Required', 'Unique', 'Min', 'Max', 'Between',
        'Email', 'Numeric', 'Integer', 'Boolean', 'In', 'Regex', 'Date',
    ];

    /**
     * Hacen falta **dos** códigos genéricos distintos: uno solo aparece en
     * cualquier servicio que rechace algo, y no reparte nada.
     */
    private const CODIGOS_MINIMOS = 2;

    /**
     * Rutas, relativas a la carpeta recibida, de los archivos que traducen
     * reglas a códigos.
     *
     * @return list<string>
     */
    public static function en(string $rutaApp): array
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

            if (! self::traduceReglas((string) file_get_contents($archivo->getPathname()))) {
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

    /**
     * **Las dos condiciones son necesarias.** No son dos señales de lo mismo
     * puestas por prudencia, y cada una tiene su caso reproducible entre las
     * pruebas: borrá una mitad y mirá cuál se rompe, que es más rápido que
     * reconstruir el argumento.
     *
     * - Contar los códigos usados con `self::` y no solo con `CodigoDeError::`
     *   es lo que ve una traducción escrita **dentro del enum de códigos**.
     *   Sin eso pasaba invisible, y el enum es de los sitios más naturales
     *   donde alguien la pondría la primera vez.
     * - Exigir que además se nombre una regla es lo que salva a una **clase
     *   con constantes que se llamen igual** —`CAMPO_REQUERIDO` y compañía,
     *   referenciadas con `self::`, sin ninguna intención de traducir—. Sin
     *   eso queda roja para siempre, y un guardián que señala algo correcto de
     *   forma permanente se termina desactivando por molesto.
     *
     * El enum real cae justo en medio de los dos: usa los tres códigos
     * genéricos dentro de un método —el que traduce cada código a su status
     * HTTP, escrito además como un `match`— así que por forma y por uso es
     * indistinguible de una traducción. Lo único que lo separa es que no
     * nombra ninguna regla.
     */
    private static function traduceReglas(string $contenido): bool
    {
        return self::nombraAlgunaRegla($contenido)
            && self::codigosGenericosUsados($contenido) >= self::CODIGOS_MINIMOS;
    }

    private static function nombraAlgunaRegla(string $contenido): bool
    {
        foreach (self::NOMBRES_DE_REGLAS as $regla) {
            if (preg_match("/['\"]{$regla}['\"]/", $contenido) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Se cuentan los que se **usan** —con `CodigoDeError::` o con `self::`—, no
     * los que solo se nombran.
     *
     * Contar las dos formas cubre también una traducción escrita dentro del
     * propio enum de códigos, que es de los sitios más naturales donde alguien
     * la pondría la primera vez: el enum ya sabe de códigos, así que agregarle
     * el mapeo parece razonable. Y como el guardián existe para la copia que
     * todavía no se escribió, dejar ese sitio fuera sería dejar abierto
     * justamente por donde entraría.
     */
    private static function codigosGenericosUsados(string $contenido): int
    {
        $usados = 0;

        foreach (self::CODIGOS_GENERICOS as $codigo) {
            $usaElNombreDeLaClase = str_contains($contenido, 'CodigoDeError::'.$codigo);
            $usaSelf = str_contains($contenido, 'self::'.$codigo);

            if ($usaElNombreDeLaClase || $usaSelf) {
                $usados++;
            }
        }

        return $usados;
    }
}
