<?php

namespace Database\Seeders;

use App\Compartido\Documentos\TipoDeDocumento;
use App\Dominios\Clientes\Modelos\Cliente;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Cliente para las boletas que no exigen identificar a nadie (RF-010).
 *
 * La caja lo necesita desde la primera venta: sin él, cobrar por debajo del
 * tope obligaría a dar de alta un cliente que nadie pidió.
 *
 * Es idempotente: sembrar dos veces no crea dos. Un segundo "público general"
 * no rompería la base —el índice único es parcial y no alcanza a los que no
 * tienen documento— pero dejaría a la caja eligiendo entre dos opciones
 * idénticas.
 */
class ClientePublicoGeneralSeeder extends Seeder
{
    use WithoutModelEvents;

    public const NOMBRE = 'Público general';

    public function run(): void
    {
        Cliente::query()->firstOrCreate(
            [
                'tipo_documento' => TipoDeDocumento::SIN_DOCUMENTO->value,
                'nombre' => self::NOMBRE,
            ],
            [
                'numero_documento' => null,
                'activo' => true,
            ]
        );
    }
}
