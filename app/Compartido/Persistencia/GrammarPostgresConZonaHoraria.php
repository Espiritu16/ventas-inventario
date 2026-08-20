<?php

namespace App\Compartido\Persistencia;

use Illuminate\Database\Query\Grammars\PostgresGrammar;

/**
 * Hace que los instantes viajen a la base con su desplazamiento horario.
 *
 * Laravel envía las fechas como 'Y-m-d H:i:s', sin zona. PostgreSQL, al
 * recibir un literal sin zona en una columna `timestamptz`, lo interpreta con
 * la zona de la sesión —UTC, por configuración de la conexión—, así que una
 * hora de Lima se guardaba como si fuera UTC y el instante quedaba corrido
 * cinco horas.
 *
 * Con el desplazamiento incluido, la conversión la hace el motor y el
 * invariante de RNF-006 deja de depender de que cada llamada recuerde
 * normalizar a UTC antes de guardar.
 */
final class GrammarPostgresConZonaHoraria extends PostgresGrammar
{
    public function getDateFormat()
    {
        return 'Y-m-d H:i:sP';
    }
}
