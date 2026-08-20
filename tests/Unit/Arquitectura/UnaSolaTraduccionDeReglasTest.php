<?php

namespace Tests\Unit\Arquitectura;

use PHPUnit\Framework\TestCase;
use Tests\Soporte\DetectorDeTraduccionesDeReglas;

/**
 * La traducción de una regla de validación a un código de la taxonomía vive en
 * un solo sitio.
 *
 * `UsuarioService` llegó a tener su propia copia, escrita antes de que
 * existiera la compartida, y las dos divergieron sin que nada avisara. Una
 * copia nueva no se nota leyendo el código: funciona igual el día que se
 * escribe, y solo se nota cuando una de las dos cambia.
 *
 * El detector reconoce la traducción por lo que **es** —códigos genéricos y
 * nombres de reglas conviviendo en el mismo archivo— y no por la forma que
 * suele tener. La primera versión buscaba un `match`, y eso dejaba pasar
 * justamente el caso más probable: quien copia se lleva el `match`, pero quien
 * se hace la suya escribe lo que le sale, que es como nació la copia de
 * usuarios.
 */
final class UnaSolaTraduccionDeReglasTest extends TestCase
{
    private const UNICA_FUENTE = 'Compartido/Errores/ValidadorDeDominio.php';

    public function test_solo_el_validador_compartido_traduce_reglas_a_codigos(): void
    {
        $copias = DetectorDeTraduccionesDeReglas::en(dirname(__DIR__, 3).'/app');

        $this->assertSame(
            [self::UNICA_FUENTE],
            $copias,
            'Hay más de una traducción de reglas a códigos: '.implode(', ', $copias)
        );
    }

    /** Quien copia se lleva el `match`, aunque le cambie el nombre al método. */
    public function test_detecta_una_copia_escrita_con_match(): void
    {
        $this->assertSame(
            ['Dominios/Usuarios/Servicios/UsuarioService.php'],
            $this->detectarEnArchivo('UsuarioService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Usuarios\Servicios;

                class UsuarioService
                {
                    private function traducir(array $falladas): CodigoDeError
                    {
                        return match ($falladas[0]) {
                            'Required' => CodigoDeError::CAMPO_REQUERIDO,
                            'Min', 'Max' => CodigoDeError::CAMPO_FUERA_DE_RANGO,
                            default => CodigoDeError::CAMPO_FORMATO_INVALIDO,
                        };
                    }
                }
                CLASE)
        );
    }

    /**
     * Y quien se hace la suya escribe lo que le sale. Es el caso más probable
     * de los dos, y el que la versión anterior del detector no veía.
     */
    public function test_detecta_una_copia_escrita_con_condicionales(): void
    {
        $this->assertSame(
            ['Dominios/Clientes/Servicios/ClienteService.php'],
            $this->detectarEnArchivo('ClienteService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Clientes\Servicios;

                class ClienteService
                {
                    private function codigoDelFallo(array $falladas): CodigoDeError
                    {
                        if (in_array('Required', $falladas, true)) {
                            return CodigoDeError::CAMPO_REQUERIDO;
                        } elseif (in_array('Between', $falladas, true)) {
                            return CodigoDeError::CAMPO_FUERA_DE_RANGO;
                        }

                        return CodigoDeError::CAMPO_FORMATO_INVALIDO;
                    }
                }
                CLASE)
        );
    }

    /** Escrita como tabla, que es la tercera forma natural de escribirla. */
    public function test_detecta_una_copia_escrita_como_tabla(): void
    {
        $this->assertSame(
            ['Dominios/Compras/Servicios/CompraService.php'],
            $this->detectarEnArchivo('CompraService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Compras\Servicios;

                class CompraService
                {
                    private const CODIGOS = [
                        'Required' => CodigoDeError::CAMPO_REQUERIDO,
                        'Between' => CodigoDeError::CAMPO_FUERA_DE_RANGO,
                    ];
                }
                CLASE)
        );
    }

    /**
     * Un servicio que rechaza con códigos genéricos por su cuenta no está
     * traduciendo reglas: no nombra ninguna. Es el falso positivo que hay que
     * evitar, porque hay varios servicios así y un guardián que los señale se
     * vuelve ruido y se termina desactivando.
     */
    public function test_no_señala_a_un_servicio_que_solo_usa_los_codigos_genericos(): void
    {
        $this->assertSame(
            [],
            $this->detectarEnArchivo('CompraService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Compras\Servicios;

                class CompraService
                {
                    private function fueraDeRango(string $mensaje): ErrorDeDominio
                    {
                        return new ErrorDeDominio(CodigoDeError::CAMPO_FUERA_DE_RANGO, $mensaje);
                    }

                    private function formatoInvalido(string $mensaje): ErrorDeDominio
                    {
                        return new ErrorDeDominio(CodigoDeError::CAMPO_FORMATO_INVALIDO, $mensaje);
                    }
                }
                CLASE)
        );
    }

    /**
     * Y las reglas en minúscula son las que se escriben al declarar la
     * validación, no al traducir su fallo: un servicio que las declara y
     * además rechaza con códigos genéricos es lo normal, no una copia.
     */
    public function test_no_señala_a_un_servicio_que_declara_reglas_y_rechaza_por_su_cuenta(): void
    {
        $this->assertSame(
            [],
            $this->detectarEnArchivo('ProductoService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Catalogo\Servicios;

                class ProductoService
                {
                    public function crear(): void
                    {
                        ValidadorDeDominio::validar($campos, [
                            'codigo' => ['required', 'string', 'between:1,40'],
                            'nombre' => ['required', 'string', 'between:3,150'],
                        ]);

                        throw new ErrorDeDominio(CodigoDeError::CAMPO_FUERA_DE_RANGO, 'Fuera de rango.');
                        throw new ErrorDeDominio(CodigoDeError::CAMPO_REQUERIDO, 'Falta.');
                    }
                }
                CLASE)
        );
    }

    /**
     * El enum de la taxonomía es el sitio más natural donde alguien pondría
     * una traducción la primera vez —ya sabe de códigos—, así que el guardián
     * tiene que verla también ahí. Es el caso que más importa: el guardián
     * existe para la copia que todavía no se escribió, no para la que ya se
     * eliminó.
     */
    public function test_detecta_una_traduccion_escrita_dentro_del_enum_de_codigos(): void
    {
        $this->assertSame(
            ['Compartido/Errores/CodigoDeError.php'],
            $this->detectarEnRuta('Compartido/Errores/CodigoDeError.php', <<<'CLASE'
                <?php

                namespace App\Compartido\Errores;

                enum CodigoDeError: string
                {
                    case CAMPO_REQUERIDO = 'CAMPO_REQUERIDO';
                    case CAMPO_FORMATO_INVALIDO = 'CAMPO_FORMATO_INVALIDO';
                    case CAMPO_FUERA_DE_RANGO = 'CAMPO_FUERA_DE_RANGO';

                    public static function segunRegla(string $regla): self
                    {
                        return match ($regla) {
                            'Required' => self::CAMPO_REQUERIDO,
                            'Between' => self::CAMPO_FUERA_DE_RANGO,
                            default => self::CAMPO_FORMATO_INVALIDO,
                        };
                    }
                }
                CLASE)
        );
    }

    /**
     * Y el enum tal como está no se dispara, que es lo que hace que el cierre
     * de arriba no traiga un falso positivo permanente.
     *
     * Usa los tres códigos genéricos dentro de un método —el que traduce cada
     * código a su status HTTP— y encima escrito como un `match`, así que por
     * forma y por uso es indistinguible de una traducción. Lo único que lo
     * separa es que no nombra ninguna regla.
     */
    public function test_no_señala_al_enum_de_codigos_tal_como_esta(): void
    {
        $this->assertSame(
            [],
            $this->detectarEnRuta('Compartido/Errores/CodigoDeError.php', <<<'CLASE'
                <?php

                namespace App\Compartido\Errores;

                enum CodigoDeError: string
                {
                    case CAMPO_REQUERIDO = 'CAMPO_REQUERIDO';
                    case CAMPO_FORMATO_INVALIDO = 'CAMPO_FORMATO_INVALIDO';
                    case CAMPO_FUERA_DE_RANGO = 'CAMPO_FUERA_DE_RANGO';

                    public function status(): int
                    {
                        return match ($this) {
                            self::CAMPO_REQUERIDO,
                            self::CAMPO_FORMATO_INVALIDO,
                            self::CAMPO_FUERA_DE_RANGO => 422,
                        };
                    }
                }
                CLASE)
        );
    }

    /**
     * Nombrar una regla y usar **un** código genérico no es traducir: es lo que
     * hace cualquier servicio que rechace algo y de paso mencione la regla en
     * un mensaje o un comentario. Repartir entre códigos empieza con dos.
     *
     * Se cubre porque la exigencia de dos códigos era la mitad de la
     * conjunción que ninguna prueba sostenía: quitarla no hacía fallar nada, y
     * una condición que se puede borrar sin consecuencia se borra tarde o
     * temprano.
     */
    public function test_no_señala_a_quien_nombra_una_regla_con_un_solo_codigo(): void
    {
        $this->assertSame(
            [],
            $this->detectarEnRuta('Dominios/Compras/Servicios/CompraService.php', <<<'CLASE'
                <?php

                namespace App\Dominios\Compras\Servicios;

                class CompraService
                {
                    /** La regla 'Required' de Laravel no cubre el caso de la línea vacía. */
                    private function sinLineas(): ErrorDeDominio
                    {
                        return new ErrorDeDominio(CodigoDeError::CAMPO_REQUERIDO, 'Falta el detalle.');
                    }
                }
                CLASE)
        );
    }

    /**
     * Una clase cualquiera puede tener constantes que se llamen igual que los
     * códigos y referenciarlas con `self::`, sin ninguna intención de
     * traducir. Es el caso que sostiene la exigencia de nombrar una regla:
     * sin ella quedaría roja para siempre, y un guardián que señala algo
     * correcto de forma permanente se desactiva y se lleva la protección
     * entera.
     */
    public function test_no_señala_a_una_clase_con_constantes_que_se_llaman_igual(): void
    {
        $this->assertSame(
            [],
            $this->detectarEnRuta('Compartido/Formularios/Etiquetas.php', <<<'CLASE'
                <?php

                namespace App\Compartido\Formularios;

                class Etiquetas
                {
                    private const CAMPO_REQUERIDO = 'Este dato hace falta.';
                    private const CAMPO_FORMATO_INVALIDO = 'Revisá cómo lo escribiste.';
                    private const CAMPO_FUERA_DE_RANGO = 'Ese valor no entra.';

                    public function todas(): array
                    {
                        return [self::CAMPO_REQUERIDO, self::CAMPO_FORMATO_INVALIDO, self::CAMPO_FUERA_DE_RANGO];
                    }
                }
                CLASE)
        );
    }

    /**
     * @return list<string>
     */
    private function detectarEnArchivo(string $nombre, string $contenido): array
    {
        $app = sys_get_temp_dir().'/traduccion-'.uniqid();
        $carpeta = $app.'/'.dirname($this->rutaDe($nombre));
        mkdir($carpeta, 0777, true);
        file_put_contents($app.'/'.$this->rutaDe($nombre), $contenido);

        try {
            return DetectorDeTraduccionesDeReglas::en($app);
        } finally {
            self::borrarRecursivo($app);
        }
    }

    /**
     * @return list<string>
     */
    private function detectarEnRuta(string $ruta, string $contenido): array
    {
        $app = sys_get_temp_dir().'/traduccion-'.uniqid();
        mkdir($app.'/'.dirname($ruta), 0777, true);
        file_put_contents($app.'/'.$ruta, $contenido);

        try {
            return DetectorDeTraduccionesDeReglas::en($app);
        } finally {
            self::borrarRecursivo($app);
        }
    }

    private function rutaDe(string $nombre): string
    {
        return match ($nombre) {
            'UsuarioService.php' => 'Dominios/Usuarios/Servicios/UsuarioService.php',
            'ClienteService.php' => 'Dominios/Clientes/Servicios/ClienteService.php',
            'CompraService.php' => 'Dominios/Compras/Servicios/CompraService.php',
            default => 'Dominios/Catalogo/Servicios/ProductoService.php',
        };
    }

    private static function borrarRecursivo(string $ruta): void
    {
        foreach (glob($ruta.'/*') ?: [] as $hijo) {
            is_dir($hijo) ? self::borrarRecursivo($hijo) : unlink($hijo);
        }

        rmdir($ruta);
    }
}
