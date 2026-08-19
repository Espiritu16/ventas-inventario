<?php

namespace App\Compartido\Autorizacion;

use Illuminate\Http\Request;

/**
 * Lee qué componentes viene a invocar una petición de actualización de
 * Livewire.
 *
 * Se usa solo para decidir si una petición SIN sesión puede pasar. Ante
 * cualquier payload que no se entienda devuelve una lista que no puede
 * autorizarse, de modo que lo ilegible se rechaza en vez de colarse: es la
 * misma regla de deny-by-default aplicada al contenido.
 */
final class PeticionDeLivewire
{
    /**
     * Nombres de los componentes que la petición quiere actualizar.
     *
     * @return array<int, string>|null null si el payload no se puede leer
     */
    public static function componentes(Request $peticion): ?array
    {
        $componentes = $peticion->input('components');

        if (! is_array($componentes) || $componentes === []) {
            return null;
        }

        $nombres = [];

        foreach ($componentes as $componente) {
            $snapshot = $componente['snapshot'] ?? null;

            if (is_string($snapshot)) {
                $snapshot = json_decode($snapshot, true);
            }

            $nombre = $snapshot['memo']['name'] ?? null;

            if (! is_string($nombre) || $nombre === '') {
                return null;
            }

            $nombres[] = $nombre;
        }

        return $nombres;
    }
}
