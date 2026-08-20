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
     * `HumoDeInstalacion` es de S-01-B, no implementa ningún RF y su handoff
     * lo declara temporal. No se anota ni se borra desde este frente: es ruta
     * de `implementation-backend` y dos pruebas suyas siguen dependiendo de
     * él. Cuando el mecanismo entre, va a rechazarlo en ejecución.
     */
    private const PENDIENTES = [
        'App\\Dominios\\Usuarios\\Livewire\\HumoDeInstalacion',
    ];

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

    /** Los pendientes son una lista cerrada: si alguno ya declara, hay que sacarlo de acá. */
    public function test_la_lista_de_pendientes_no_envejece(): void
    {
        foreach (self::PENDIENTES as $clase) {
            if (! class_exists($clase)) {
                continue;
            }

            $this->assertSame(
                [],
                (new ReflectionClass($clase))->getAttributes(self::ATRIBUTO),
                "«{$clase}» ya declara su permiso: sacalo de PENDIENTES."
            );
        }
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
