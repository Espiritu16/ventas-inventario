<?php

namespace Tests\Soporte;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Encuentra los campos y las reglas que los servicios declaran al validar.
 *
 * Se leen del código en vez de escribirse en una lista, que es lo que hace que
 * la prueba de consistencia sirva de algo: comparar el mapa de nombres legibles
 * contra otra lista escrita a mano no cierra nada, solo mueve el problema de
 * sitio. Un campo nuevo aparece acá por el mismo acto de declararlo.
 */
final class DetectorDeCamposValidados
{
    /**
     * Solo se miran los archivos que validan.
     *
     * Sin este filtro, los `casts` de los modelos se cuelan: `'intentos' =>
     * 'integer'` y `'valores_nuevos' => 'array'` son indistinguibles de una
     * regla mirando la línea sola, y metían doce campos inventados en la
     * lista. Un modelo no valida, así que alcanza con preguntar si el archivo
     * llama al validador.
     */
    private const LLAMADAS_AL_VALIDADOR = [
        'ValidadorDeDominio::validar',
        'Validator::make',
    ];

    /**
     * Reglas de Laravel que el proyecto puede declarar. Sirven para reconocer
     * que un `'clave' => [...]` es una declaración de validación y no otro
     * array cualquiera.
     */
    private const REGLAS_CONOCIDAS = [
        'required', 'sometimes', 'nullable', 'string', 'integer', 'boolean',
        'array', 'numeric', 'email', 'date', 'date_format', 'regex', 'in',
        'between', 'min', 'max', 'unique', 'exists', 'confirmed', 'digits',
        'size', 'after', 'before', 'url', 'alpha', 'alpha_num', 'alpha_dash',
        'distinct', 'filled', 'present',
    ];

    /**
     * Nombres de campo que aparecen en un array de reglas.
     *
     * @return list<string>
     */
    public static function campos(string $rutaApp): array
    {
        $campos = [];

        foreach (self::declaraciones($rutaApp) as [$campo, $reglas]) {
            $campos[] = $campo;
        }

        $campos = array_values(array_unique($campos));
        sort($campos);

        return $campos;
    }

    /**
     * Reglas efectivamente usadas, sin sus argumentos: `between:1,40` cuenta
     * como `between`.
     *
     * @return list<string>
     */
    public static function reglas(string $rutaApp): array
    {
        $reglas = [];

        foreach (self::declaraciones($rutaApp) as [$campo, $declaradas]) {
            foreach ($declaradas as $regla) {
                $reglas[] = $regla;
            }
        }

        $reglas = array_values(array_unique($reglas));
        sort($reglas);

        return $reglas;
    }

    /**
     * Pares campo/reglas de cada declaración encontrada.
     *
     * Se aceptan las dos formas que Laravel admite —lista de reglas y cadena
     * separada por barras— porque reconocer solo la que hoy se usa dejaría
     * ciego al detector el día que alguien escriba la otra.
     *
     * **Límite conocido:** una regla única escrita como cadena suelta
     * —`'activo' => 'boolean'`— no se reconoce, porque es indistinguible de
     * cualquier otro par clave-valor. `['campo' => 'email']`, que es como los
     * servicios nombran el campo de un error, tiene exactamente esa forma y se
     * colaba como si fuera un campo validado. El proyecto declara siempre con
     * lista, así que el límite no deja nada afuera hoy; queda escrito porque
     * el día que alguien use esa forma, el campo no llega al mapa y nada
     * avisa.
     *
     * @return list<array{0: string, 1: list<string>}>
     */
    private static function declaraciones(string $rutaApp): array
    {
        $encontradas = [];

        foreach (self::archivos($rutaApp) as $archivo) {
            $contenido = (string) file_get_contents($archivo);

            if (! self::valida($contenido)) {
                continue;
            }

            preg_match_all(
                "/'([a-z_]+)'\s*=>\s*(\[[^\]]*\]|'[a-z_|:,0-9\/^\$.\\\\A-Z-]+')/",
                $contenido,
                $coincidencias,
                PREG_SET_ORDER
            );

            foreach ($coincidencias as [$todo, $campo, $valor]) {
                $reglas = self::reglasDe($valor);

                if ($reglas !== []) {
                    $encontradas[] = [$campo, $reglas];
                }
            }
        }

        return $encontradas;
    }

    private static function valida(string $contenido): bool
    {
        foreach (self::LLAMADAS_AL_VALIDADOR as $llamada) {
            if (str_contains($contenido, $llamada)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function reglasDe(string $valor): array
    {
        if (str_starts_with($valor, '[')) {
            $piezas = self::literalesDe($valor);
        } elseif (str_contains($valor, '|')) {
            $piezas = explode('|', trim($valor, "'"));
        } else {
            return [];
        }

        $reglas = [];

        foreach ($piezas as $pieza) {
            $nombre = explode(':', $pieza)[0];

            if (in_array($nombre, self::REGLAS_CONOCIDAS, true)) {
                $reglas[] = $nombre;
            }
        }

        return $reglas;
    }

    /**
     * @return list<string>
     */
    private static function literalesDe(string $array): array
    {
        preg_match_all("/'([^']*)'/", $array, $coincidencias);

        return $coincidencias[1];
    }

    /**
     * @return list<string>
     */
    private static function archivos(string $rutaApp): array
    {
        $rutas = [];

        $archivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(rtrim($rutaApp, DIRECTORY_SEPARATOR), RecursiveDirectoryIterator::SKIP_DOTS)
        );

        /** @var SplFileInfo $archivo */
        foreach ($archivos as $archivo) {
            if ($archivo->getExtension() === 'php') {
                $rutas[] = $archivo->getPathname();
            }
        }

        return $rutas;
    }
}
