<?php

namespace Tests\Feature\Livewire;

use ReflectionClass;
use Tests\TestCase;

/**
 * Que todo componente declare su permiso.
 *
 * Es un COMPLEMENTO del mecanismo en ejecución, nunca su sustituto: el hook
 * es lo que hace que un componente sin declarar falle, y esta prueba solo
 * avisa antes. Se decidió así en actores-permisos.md, y el motivo es que una
 * prueba que tenga que adivinar qué métodos escriben es frágil —el guardián
 * de S-00 reconocía dominios por sufijo y era ciego a la nomenclatura
 * española—. Ésta no adivina nada: mira si el atributo está o no está.
 *
 * Existe porque hizo falta. Al anotar los dos componentes de S-01-F, la
 * edición del atributo de clase de `ListaDeUsuarios` no se aplicó y no falló
 * nada: la suite quedó verde con el componente sin declarar, porque el
 * atributo todavía no lo lee nadie. Lo detectó una comprobación por reflexión
 * hecha a mano, y esto es esa comprobación vuelta permanente.
 */
final class DeclaracionDePermisoTest extends TestCase
{
    private const ATRIBUTO = 'App\\Compartido\\Autorizacion\\Permiso';

    /**
     * Componentes que todavía no declaran, con su motivo.
     *
     * **Hoy está vacía, y eso es el estado correcto**: todos los componentes
     * declaran. La constante se conserva porque un sprint futuro puede toparse
     * con un componente ajeno sin anotar —fue lo que pasó con
     * `HumoDeInstalacion`, que era de `implementation-backend` y no se podía
     * anotar desde acá— y entonces hace falta un lugar donde registrar la
     * excepción con su motivo, en vez de silenciar la prueba.
     *
     * Con la lista vacía, `test_la_lista_de_pendientes_no_envejece` no
     * comprueba nada, que es lo que corresponde cuando no hay pendientes: la
     * garantía de que ningún componente queda sin declarar la sostiene la otra
     * prueba, que sí recorre todos.
     *
     * @var array<int, class-string>
     */
    private const PENDIENTES = [];

    public function test_todo_componente_declara_su_permiso(): void
    {
        $sinDeclarar = [];

        foreach ($this->componentes() as $clase) {
            if (in_array($clase, self::PENDIENTES, true)) {
                continue;
            }

            if ((new ReflectionClass($clase))->getAttributes(self::ATRIBUTO) === []) {
                $sinDeclarar[] = $clase;
            }
        }

        $this->assertSame(
            [],
            $sinDeclarar,
            'Estos componentes no declaran permiso. Sin el atributo, el mecanismo los rechaza.'
        );
    }

    /**
     * Los pendientes son una lista cerrada: si alguno ya declara, hay que
     * sacarlo de acá.
     *
     * Se afirma sobre el conjunto y no dentro de un bucle para que la prueba
     * haga siempre una aserción, también con la lista vacía. Un bucle sobre
     * una lista vacía no comprueba nada y PHPUnit lo marca arriesgado con
     * razón: una prueba sin aserciones pasa siempre, y la que hoy no tiene
     * nada que mirar es indistinguible de la que dejó de mirar.
     */
    public function test_la_lista_de_pendientes_no_envejece(): void
    {
        $yaDeclaran = array_values(array_filter(
            self::PENDIENTES,
            fn (string $clase) => class_exists($clase)
                && (new ReflectionClass($clase))->getAttributes(self::ATRIBUTO) !== []
        ));

        $this->assertSame(
            [],
            $yaDeclaran,
            'Estos ya declaran su permiso: sacalos de PENDIENTES.'
        );
    }

    /** @return array<int, class-string> */
    private function componentes(): array
    {
        $clases = [];

        foreach (glob(app_path('Dominios/*/Livewire/*.php')) ?: [] as $archivo) {
            $dominio = basename(dirname($archivo, 2));
            $clases[] = 'App\\Dominios\\'.$dominio.'\\Livewire\\'.basename($archivo, '.php');
        }

        $this->assertNotEmpty($clases, 'No se encontró ningún componente: el barrido está mirando mal.');

        return $clases;
    }
}
