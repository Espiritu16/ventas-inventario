<?php

namespace Tests\Unit\Autorizacion;

use App\Compartido\Autorizacion\MatrizDePermisos;
use PHPUnit\Framework\TestCase;

/**
 * La matriz de código es una transcripción a mano de la tabla de
 * `docs/requisitos/actores-permisos.md`. Son dos valores que hay que mantener
 * iguales, y hasta ahora nada comprobaba que lo estuvieran.
 *
 * No es teórico: el documento declaró que el vendedor no accede al catálogo y
 * el código siguió permitiéndoselo. Nada falló — el código es el que decide y
 * el documento el que se lee, así que la divergencia solo se ve leyendo los
 * dos, que es lo que nadie hace salvo por casualidad.
 *
 * Esto no convierte el documento en generador: la matriz se sigue escribiendo
 * a mano, con su criterio y sus comentarios. Lo único que cambia es que
 * separarse deje de ser gratis.
 */
final class MatrizCoincideConElDocumentoTest extends TestCase
{
    private const DOCUMENTO = __DIR__.'/../../../docs/requisitos/actores-permisos.md';

    /**
     * Filas de la tabla que declaran un permiso sobre una operación con verbo
     * y ruta. Se ignoran las de jobs y comandos, que no son rutas.
     *
     * @return array<string, array<int, string>> identificador => roles permitidos
     */
    private function declaradoEnElDocumento(): array
    {
        $lineas = file(self::DOCUMENTO, FILE_IGNORE_NEW_LINES);
        $esperado = [];

        foreach ($lineas as $linea) {
            if (! str_starts_with(trim($linea), '|')) {
                continue;
            }

            $celdas = array_map('trim', explode('|', trim($linea, '| ')));

            // | Actor | Rol técnico | Recurso/Operación | Acción | Condición | Permitido | Deriva de |
            if (count($celdas) < 6) {
                continue;
            }

            [$actor, $rol, $recurso] = [$celdas[0], $celdas[1], $celdas[2]];
            $permitido = $celdas[5];

            if (preg_match('#^(GET|POST|PATCH|PUT|DELETE) /\S*$#', $recurso) !== 1) {
                continue;
            }

            // El actor Anónimo y el Sistema no tienen rol técnico comparable
            // con la tabla de roles del código.
            if (! in_array($rol, ['administrador', 'vendedor'], true)) {
                continue;
            }

            $esperado[$recurso] ??= [];

            if (str_starts_with(mb_strtolower($permitido), 'sí')) {
                $esperado[$recurso][] = $rol;
            }
        }

        return $esperado;
    }

    public function test_el_documento_tiene_filas_que_comparar(): void
    {
        $this->assertNotEmpty(
            $this->declaradoEnElDocumento(),
            'No se leyó ninguna fila: si el formato de la tabla cambió, esta prueba '
            .'pasaría a no comparar nada y quedaría verde sin comprobar.'
        );
    }

    /**
     * Una fila declarada en el documento que el código no transcribió, **dentro
     * de un recurso que el código ya conoce**.
     *
     * No se exige el documento entero: declara el horizonte completo, incluidas
     * compras, ventas y comprobantes, que sus sprints todavía no implementaron.
     * Exigirlas haría fallar esta prueba durante meses por trabajo que nadie
     * empezó, y una prueba que falla por algo que no es un defecto se termina
     * silenciando.
     *
     * Lo que sí se exige es coherencia por recurso: si el código transcribió
     * alguna fila de `/productos`, tiene que tenerlas todas. Ese es el olvido
     * real —agregar el listado y no el detalle, o el alta y no la edición— y es
     * el que deja una operación sin permiso declarado dentro de una pantalla que
     * ya existe.
     */
    public function test_no_falta_ninguna_fila_de_un_recurso_ya_transcrito(): void
    {
        $enCodigo = MatrizDePermisos::porRol();
        $recursosConocidos = array_unique(array_map(
            fn (string $ruta) => $this->recursoBase($ruta),
            array_keys($enCodigo)
        ));

        $faltantes = [];

        foreach ($this->declaradoEnElDocumento() as $ruta => $roles) {
            if ($roles === [] || array_key_exists($ruta, $enCodigo)) {
                continue;
            }

            if (in_array($this->recursoBase($ruta), $recursosConocidos, true)) {
                $faltantes[] = $ruta;
            }
        }

        $this->assertSame(
            [],
            $faltantes,
            'El código transcribió parte de estos recursos y se dejó filas: '.implode(', ', $faltantes)
        );
    }

    /** `PATCH /usuarios/{id}` y `GET /usuarios` comparten recurso base. */
    private function recursoBase(string $identificador): string
    {
        [, $uri] = explode(' ', $identificador, 2);

        return '/'.strtok(ltrim($uri, '/'), '/');
    }

    /**
     * Una entrada del código que el documento no declara.
     *
     * Es la dirección que atrapa un permiso agregado a mano sin pasar por la
     * gobernanza, que es peor que el olvido inverso: nadie lo aprobó.
     */
    public function test_toda_entrada_del_codigo_esta_en_el_documento(): void
    {
        $enDocumento = $this->declaradoEnElDocumento();
        $sobrantes = [];

        foreach (array_keys(MatrizDePermisos::porRol()) as $ruta) {
            if (! array_key_exists($ruta, $enDocumento)) {
                $sobrantes[] = $ruta;
            }
        }

        $this->assertSame([], $sobrantes, 'Entradas del código que ningún documento declara: '.implode(', ', $sobrantes));
    }

    /** Y que los roles de cada una coincidan, no solo que la ruta exista. */
    public function test_los_roles_de_cada_ruta_coinciden(): void
    {
        $enCodigo = MatrizDePermisos::porRol();
        $divergencias = [];

        foreach ($this->declaradoEnElDocumento() as $ruta => $rolesDelDocumento) {
            if (! array_key_exists($ruta, $enCodigo)) {
                continue; // lo cubre la prueba anterior
            }

            $delCodigo = $enCodigo[$ruta];
            sort($rolesDelDocumento);
            sort($delCodigo);

            if ($rolesDelDocumento !== $delCodigo) {
                $divergencias[] = sprintf(
                    '%s — documento: [%s], código: [%s]',
                    $ruta,
                    implode(', ', $rolesDelDocumento) ?: 'ninguno',
                    implode(', ', $delCodigo) ?: 'ninguno',
                );
            }
        }

        $this->assertSame([], $divergencias, "El código y el documento no dicen lo mismo:\n- ".implode("\n- ", $divergencias));
    }
}
