<?php

namespace Tests\Soporte;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Encuentra clases de dominio ubicadas fuera de `app/Dominios/` y
 * `app/Compartido/`.
 *
 * Trabaja sobre el texto del archivo, sin cargar la clase: una clase mal
 * ubicada suele tener además un namespace que no corresponde a su ruta, así
 * que el autoload no la encontraría.
 */
final class DetectorDeClasesDeDominio
{
    /**
     * Carpetas de primer nivel dentro de `app/` donde el código de dominio sí
     * puede vivir.
     */
    private const UBICACIONES_PERMITIDAS = ['Dominios', 'Compartido'];

    /**
     * Rutas, relativas a la carpeta recibida, de las clases de dominio que
     * están donde no corresponde.
     *
     * @return list<string>
     */
    public static function malUbicadas(string $rutaApp): array
    {
        $rutaApp = rtrim($rutaApp, DIRECTORY_SEPARATOR);
        $encontradas = [];

        $archivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rutaApp, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $archivo */
        foreach ($archivos as $archivo) {
            if ($archivo->getExtension() !== 'php') {
                continue;
            }

            $rutaRelativa = substr($archivo->getPathname(), strlen($rutaApp) + 1);

            if (self::estaEnUbicacionPermitida($rutaRelativa)) {
                continue;
            }

            if (self::esClaseDeDominio((string) file_get_contents($archivo->getPathname()))) {
                $encontradas[] = str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativa);
            }
        }

        sort($encontradas);

        return $encontradas;
    }

    private static function estaEnUbicacionPermitida(string $rutaRelativa): bool
    {
        $primerSegmento = strtok(str_replace(DIRECTORY_SEPARATOR, '/', $rutaRelativa), '/');

        return in_array($primerSegmento, self::UBICACIONES_PERMITIDAS, true);
    }

    /**
     * Una clase es de dominio si es un modelo Eloquent o si su nombre la
     * declara servicio o repositorio del negocio.
     */
    private static function esClaseDeDominio(string $contenido): bool
    {
        if (preg_match('/\bextends\s+\\\\?(?:Illuminate\\\\Database\\\\Eloquent\\\\)?Model\b/', $contenido) === 1) {
            return true;
        }

        return preg_match('/\b(?:class|interface)\s+\w+(?:Service|Repository)\b/', $contenido) === 1;
    }
}
