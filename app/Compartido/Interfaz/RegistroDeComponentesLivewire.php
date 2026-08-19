<?php

namespace App\Compartido\Interfaz;

use Livewire\Livewire;

/**
 * Registra los componentes Livewire que viven dentro de cada dominio.
 *
 * Livewire busca por defecto en `App\Livewire`, un solo lugar por capa
 * técnica, que es justo lo contrario de la organización por dominio de
 * ADR-0005. En vez de mover los componentes, se registran desde donde están.
 *
 * El descubrimiento es automático a propósito: si cada componente hubiera que
 * declararlo a mano en el service provider, agregar una pantalla obligaría a
 * tocar un archivo del backend, y las pantallas son del frente de interfaz.
 *
 * El nombre queda como `<dominio>.<componente>` en kebab-case; por ejemplo
 * `App\Dominios\Usuarios\Livewire\ListaDeUsuarios` se invoca como
 * `<livewire:usuarios.lista-de-usuarios />`.
 */
final class RegistroDeComponentesLivewire
{
    public static function registrar(string $rutaDominios): void
    {
        foreach (glob($rutaDominios.'/*/Livewire/*.php') ?: [] as $archivo) {
            $dominio = basename(dirname($archivo, 2));
            $componente = basename($archivo, '.php');

            Livewire::component(
                self::nombre($dominio, $componente),
                "App\\Dominios\\{$dominio}\\Livewire\\{$componente}"
            );
        }
    }

    private static function nombre(string $dominio, string $componente): string
    {
        return self::kebab($dominio).'.'.self::kebab($componente);
    }

    private static function kebab(string $texto): string
    {
        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $texto));
    }
}
