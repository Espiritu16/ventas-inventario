<?php

namespace Tests\Unit\Arquitectura;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * ADR-0004: toda escritura de stock pasa por `InventarioService`.
 *
 * Si otra clase escribiera en `lotes` o en el kardex, el saldo y el historial
 * podrían divergir —y el saldo es lo que decide si una venta se puede cobrar—.
 * La transacción y el bloqueo de fila que protegen esa coherencia viven en el
 * servicio; escribir por fuera los saltea sin que nada falle.
 */
final class PuertaUnicaDeInventarioTest extends TestCase
{
    /** Clases autorizadas a escribir stock, con su motivo. */
    private const AUTORIZADAS = [
        // La puerta única.
        'App\Dominios\Inventario\Servicios\InventarioService',
    ];

    public function test_solo_el_servicio_de_inventario_escribe_el_stock(): void
    {
        $infractoras = [];

        foreach ($this->clasesDeLaAplicacion() as $clase => $contenido) {
            if (in_array($clase, self::AUTORIZADAS, true)) {
                continue;
            }

            if ($this->escribeStock($contenido)) {
                $infractoras[] = $clase;
            }
        }

        sort($infractoras);

        $this->assertSame(
            [],
            $infractoras,
            "Estas clases escriben stock sin pasar por InventarioService:\n- ".implode("\n- ", $infractoras)
        );
    }

    /** El detector reconoce la escritura, no solo el nombre del modelo. */
    public function test_el_detector_reconoce_una_escritura_de_stock(): void
    {
        $this->assertTrue($this->escribeStock('Lote::query()->create([]);'));
        $this->assertTrue($this->escribeStock('MovimientoInventario::query()->create([]);'));
        $this->assertTrue($this->escribeStock("DB::table('lotes')->update([]);"));
        $this->assertTrue($this->escribeStock('$lote->cantidad_actual = "1"; $lote->save();'));

        $this->assertFalse($this->escribeStock('Lote::query()->where("id", 1)->get();'), 'Leer no es escribir.');
        $this->assertFalse($this->escribeStock('$this->inventario->ingresar(...);'), 'Usar el servicio es lo correcto.');
    }

    private function escribeStock(string $contenido): bool
    {
        $patrones = [
            '/\b(Lote|MovimientoInventario)::query\(\)->(create|insert|update|delete|firstOrCreate|updateOrCreate)\b/',
            '/\b(Lote|MovimientoInventario)::(create|insert|destroy)\b/',
            '/DB::table\(\s*[\'"](lotes|movimientos_inventario)[\'"]\s*\)->(insert|update|delete|upsert)\b/',
            '/->cantidad_actual\s*=/',
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $contenido) === 1) {
                return true;
            }
        }

        return false;
    }

    /** @return array<string, string> */
    private function clasesDeLaAplicacion(): array
    {
        $raiz = dirname(__DIR__, 3).'/app';
        $clases = [];

        /** @var SplFileInfo $archivo */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, RecursiveDirectoryIterator::SKIP_DOTS)) as $archivo) {
            if ($archivo->getExtension() !== 'php') {
                continue;
            }

            $contenido = (string) file_get_contents($archivo->getPathname());
            $relativa = substr($archivo->getPathname(), strlen($raiz) + 1);
            $clases['App\\'.str_replace(['/', '.php'], ['\\', ''], $relativa)] = $contenido;
        }

        return $clases;
    }
}
