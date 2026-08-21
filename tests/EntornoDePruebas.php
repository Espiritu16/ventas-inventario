<?php

namespace Tests;

use Dotenv\Dotenv;
use PDO;
use PDOException;

/**
 * Le da a cada árbol de trabajo su propia base de pruebas, sin que nadie tenga
 * que acordarse de exportar nada.
 *
 * El problema que resuelve: `phpunit.xml` está versionado, así que fijar ahí el
 * nombre de la base obliga a todos los worktrees a compartir una sola, y
 * `RefreshDatabase` ejecuta `migrate:fresh` — dos suites simultáneas se
 * destruyen entre sí. Pero quitar esa línea sin más es peor: el `.env` apunta a
 * `ventas_inventario`, la base de DESARROLLO, y el primer `migrate:fresh` la
 * borraría. Por eso el nombre no se omite: se calcula.
 *
 * El discriminante se busca en tres fuentes, en orden, porque ninguna sirve
 * sola:
 *
 * 1. `DB_DATABASE` exportada en el entorno real del proceso — control
 *    explícito, gana siempre. Es lo que deja seguir usando
 *    `ventas_inventario_test` a quien trabaje sin paralelismo.
 * 2. `TEST_TOKEN` — el que asigna `php artisan test --parallel`; es el
 *    mecanismo estándar del framework para distinguir procesos concurrentes.
 * 3. La ruta del árbol de trabajo — el caso local de los worktrees por carril.
 *
 * El orden importa: derivar solo de la ruta funciona en esta máquina y no sirve
 * en CI, donde dos jobs en paralelo suelen compartir la misma ruta de checkout.
 * Una prueba local nunca delataría eso. Con (1) y (2) por delante, el CI tiene
 * dos formas de separar sus bases sin depender de la ruta.
 *
 * Esta es la primera de las dos guardas: valida el nombre en el bootstrap,
 * antes de que nada se conecte. La segunda vive en `Tests\TestCase` y le
 * pregunta el nombre efectivo al motor, que es lo único que detecta un `DB_URL`
 * pisando a `DB_DATABASE`.
 *
 * Regla dura: si no se puede determinar o preparar la base, esto aborta con un
 * mensaje explícito. Nunca cae a otra base — caer significaría, precisamente,
 * borrar la de desarrollo.
 */
final class EntornoDePruebas
{
    /**
     * Convención decidida antes de la ola 3: `ventas_inventario_<carril>_test`.
     *
     * El sufijo va al final y no al principio. La guarda de `Tests\TestCase`
     * exige que el nombre efectivo termine en `_test`, así que un nombre como
     * `ventas_inventario_test_s02b` —el orden que sale natural al escribirlo—
     * sería rechazado por la propia protección.
     */
    public const PREFIJO = 'ventas_inventario_';

    public const SUFIJO = '_test';

    /**
     * PostgreSQL trunca los identificadores a 63 bytes, no a 64. Un nombre más
     * largo no fallaría: se conectaría a *otra* base, en silencio.
     */
    private const LARGO_MAXIMO = 63;

    public static function preparar(string $raiz): void
    {
        // Se lee antes de tocar el .env: acá solo puede haber llegado desde el
        // entorno real del proceso, no del archivo.
        $exportada = getenv('DB_DATABASE');
        $token = getenv('TEST_TOKEN');

        $base = is_string($exportada) && $exportada !== ''
            ? $exportada
            : self::nombreDerivadoDe($raiz, is_string($token) && $token !== '' ? $token : null);

        self::verificarNombre($base);
        self::crearSiFalta($base, self::credenciales($raiz));
        self::exportar($base);
    }

    /**
     * Nombre estable por árbol de trabajo: el basename hace legible de quién es
     * la base, y el hash de la ruta completa evita que dos worktrees con el
     * mismo nombre en carpetas distintas colisionen.
     *
     * El token de paralelismo, cuando existe, se suma al hash y además se
     * escribe al final: en CI la ruta puede ser idéntica entre jobs, y sin él
     * dos procesos concurrentes caerían en la misma base — el defecto que este
     * cambio viene a corregir.
     */
    public static function nombreDerivadoDe(string $raiz, ?string $token = null): string
    {
        $etiqueta = self::soloMinusculasYGuionBajo(basename($raiz));
        $etiqueta = trim(substr($etiqueta, 0, 20), '_');
        $huella = substr(sha1($raiz.'|'.($token ?? '')), 0, 8);

        $carril = trim($etiqueta.'_'.$huella, '_');

        if ($token !== null) {
            $carril .= '_'.substr(self::soloMinusculasYGuionBajo($token), 0, 8);
        }

        return self::PREFIJO.$carril.self::SUFIJO;
    }

    private static function soloMinusculasYGuionBajo(string $texto): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', strtolower($texto)), '_');
    }

    private static function verificarNombre(string $base): void
    {
        // El nombre viaja a un CREATE DATABASE como identificador, así que se
        // acota a lo que PostgreSQL acepta sin ambigüedad de mayúsculas ni
        // truncado silencioso.
        if (preg_match('/^[a-z0-9_]{1,'.self::LARGO_MAXIMO.'}$/', $base) !== 1) {
            self::abortar(
                'El nombre de base de pruebas «'.$base.'» no es válido para PostgreSQL: '
                .'solo se admiten minúsculas, dígitos y guion bajo, hasta '.self::LARGO_MAXIMO.' caracteres.'
            );
        }

        if (! str_starts_with($base, self::PREFIJO) || ! str_ends_with($base, self::SUFIJO)) {
            self::abortar(
                'DB_DATABASE vale «'.$base.'», que no respeta la convención '
                .'«'.self::PREFIJO.'<carril>'.self::SUFIJO.'». Se aborta en vez de correr: '
                .'una suite mal apuntada no falla, borra la base entera con `migrate:fresh` y sigue en verde.'
            );
        }
    }

    /**
     * Las credenciales salen del `.env` del propio árbol, pero se leen sin
     * cargarlas al entorno: el `.env` lo carga Laravel más tarde y meterle mano
     * antes cambiaría qué variables considera «externas» al arrancar.
     *
     * @return array<string, string|null>
     */
    private static function credenciales(string $raiz): array
    {
        return Dotenv::createArrayBacked($raiz)->safeLoad();
    }

    /**
     * @param  array<string, string|null>  $credenciales
     */
    private static function crearSiFalta(string $base, array $credenciales): void
    {
        $host = $credenciales['DB_HOST'] ?? '127.0.0.1';
        $puerto = $credenciales['DB_PORT'] ?? '5432';

        try {
            // `postgres` es la base de mantenimiento: hay que estar conectado a
            // alguna para poder crear otra, y no puede ser la que se va a crear.
            $pdo = new PDO(
                "pgsql:host={$host};port={$puerto};dbname=postgres",
                $credenciales['DB_USERNAME'] ?? 'postgres',
                $credenciales['DB_PASSWORD'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );

            try {
                // PostgreSQL no tiene `CREATE DATABASE IF NOT EXISTS`. Consultar
                // `pg_database` y crear después deja una ventana entre la
                // consulta y la creación por la que se cuelan dos procesos
                // paralelos; capturar el duplicado no deja ninguna.
                $pdo->exec('create database "'.$base.'"');
            } catch (PDOException $e) {
                // 42P04 = duplicate_database: ya existe, que es exactamente lo
                // que se quería.
                if ($e->getCode() !== '42P04') {
                    throw $e;
                }
            }
        } catch (PDOException $e) {
            self::abortar(
                'No se pudo preparar la base de pruebas «'.$base.'» en '.$host.':'.$puerto.'. '
                .'Hace falta que PostgreSQL esté levantado y que el rol tenga CREATEDB. '
                .'Error: '.$e->getMessage()
            );
        }
    }

    private static function exportar(string $base): void
    {
        // Los tres canales: el repositorio de entorno de Laravel es inmutable,
        // así que con el valor ya puesto antes de que arranque, el .env no puede
        // pisarlo.
        putenv('DB_DATABASE='.$base);
        $_ENV['DB_DATABASE'] = $base;
        $_SERVER['DB_DATABASE'] = $base;
    }

    private static function abortar(string $mensaje): never
    {
        fwrite(STDERR, PHP_EOL.'[entorno de pruebas] '.$mensaje.PHP_EOL.PHP_EOL);

        exit(1);
    }
}
