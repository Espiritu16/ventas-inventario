<?php

namespace Database\Seeders;

use App\Dominios\Comprobantes\Modelos\SerieComprobante;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Series iniciales de numeración (RF-014).
 *
 * Sin una serie activa no se puede vender: el correlativo se reserva antes de
 * tocar el stock, así que la caja quedaría bloqueada desde la primera venta.
 * Idempotente, como el resto de los seeders del proyecto.
 */
class SeriesComprobanteSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            ['tipo_comprobante' => SerieComprobante::TIPO_FACTURA, 'serie' => 'F001'],
            ['tipo_comprobante' => SerieComprobante::TIPO_BOLETA, 'serie' => 'B001'],
        ] as $serie) {
            SerieComprobante::query()->firstOrCreate($serie, ['correlativo_actual' => 0, 'activo' => true]);
        }
    }
}
