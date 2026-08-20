<?php

namespace Tests\Feature\Concurrencia;

use App\Compartido\Auditoria\AuditoriaService;
use App\Dominios\Catalogo\Modelos\Producto;
use App\Dominios\Clientes\Modelos\Cliente;
use App\Dominios\Inventario\Modelos\Lote;
use App\Dominios\Inventario\Modelos\MovimientoInventario;
use App\Dominios\Inventario\Servicios\InventarioService;
use App\Dominios\Usuarios\Modelos\Usuario;
use Database\Seeders\SeriesComprobanteSeeder;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

/**
 * RNF-003: el saldo del lote y su kardex no pueden divergir cuando dos
 * operaciones compiten por la misma fila.
 *
 * **Por qué esta prueba existe y por qué lanza procesos.** S-04-B demostró el
 * bloqueo con dos procesos a mano, pero ninguna prueba de la suite lo cubría:
 * quitar el `lockForUpdate()` no hacía fallar nada. El mecanismo era correcto
 * y su ausencia, indetectable. Y el fallo que causaría no falla: las dos
 * operaciones responden `ok` y la divergencia solo aparece al cuadrar el
 * inventario, semanas después.
 *
 * Dos llamadas dentro del mismo proceso no sirven: comparten conexión, se
 * ejecutan uña tras otra y pasan con bloqueo y sin él.
 *
 * Forma especificada por `qa` al validar S-04-B.
 */
#[Group('concurrencia')]
final class BloqueoDeLoteTest extends TestCase
{
    /** Margen para que todos los kernels estén arrancados antes de competir. */
    private const SEGUNDOS_DE_BARRERA = 3.0;

    private const RONDAS = 2;

    /**
     * Pares de competidores por ronda: cada par es un ajuste y una venta.
     *
     * Con un solo par la prueba pasaba **igual sin bloqueo**: la ventana entre
     * leer y escribir dura microsegundos, y dos procesos sincronizados al
     * arrancar rara vez la comparten. Con tres pares la colisión es
     * consistente — medido: 3 de 3 rondas divergen con el bloqueo quitado, y
     * 0 de 3 con él puesto.
     */
    private const PARES = 3;

    /**
     * No usa RefreshDatabase: esa envoltura corre dentro de una transacción y
     * los procesos hijos, con su propia conexión, no verían nada de lo que
     * prepara. Cada ronda arma su escenario con `migrate:fresh`.
     *
     * Y por eso mismo deja la base vacía al terminar: sin esto, los datos que
     * quedan se filtran a las pruebas que corren después y las hacen fallar
     * por algo que no es suyo.
     */
    protected function tearDown(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true]);

        parent::tearDown();
    }

    public function test_un_ajuste_y_una_venta_simultaneos_no_separan_saldo_y_kardex(): void
    {
        $rondasContadas = 0;

        for ($ronda = 1; $ronda <= self::RONDAS; $ronda++) {
            $escenario = $this->prepararEscenario();

            // Salvaguarda: si el escenario ya está incoherente, la ronda no
            // mide el bloqueo sino su propia preparación. Se descarta.
            if (! $escenario['coherente_al_inicio']) {
                fwrite(STDERR, "\nronda {$ronda} descartada: el escenario nació incoherente\n");

                continue;
            }

            $rondasContadas++;
            $respuestas = $this->competir($escenario);

            // Que los competidores hayan CORRIDO se comprueba, no se supone.
            // La primera versión de esta prueba pasaba en verde con el bloqueo
            // quitado porque los procesos morían al arrancar y nadie competía:
            // una prueba de concurrencia que no verifica que hubo concurrencia
            // mide el vacío.
            $this->assertCompitieronDeVerdad($respuestas, $escenario, $ronda);

            $this->assertCoherente(
                $escenario['lote_id'],
                "Ronda {$ronda}: saldo y kardex divergen. Respuestas: ".json_encode($respuestas)
            );

            $this->assertCorrelativosSinRepetir($ronda);
            $this->assertNingunaOperacionSeCayo($respuestas, $ronda);
        }

        $this->assertGreaterThan(0, $rondasContadas, 'Ninguna ronda pudo medirse: revisá la preparación del escenario.');
    }

    /**
     * @return array<string, mixed>
     */
    private function prepararEscenario(): array
    {
        $this->artisan('migrate:fresh', ['--force' => true]);
        $this->seed(SeriesComprobanteSeeder::class);

        $usuario = Usuario::factory()->administrador()->create();
        $producto = Producto::factory()->create(['precio_menor' => '10.0000', 'precio_mayor' => '8.0000']);
        $cliente = Cliente::factory()->create(['direccion' => 'Av. Siempre Viva 123']);

        // El lote se crea con el servicio real, nunca con factory: un lote de
        // factory tiene saldo sin movimiento de ingreso y diverge por
        // construcción, no por concurrencia.
        $lote = (new InventarioService(new AuditoriaService))->ingresar(
            (int) $producto->id, '200.000', '5.0000', 'L-001',
            now()->addMonths(6)->format('Y-m-d'),
            MovimientoInventario::ORIGEN_COMPRA, 1, (int) $usuario->id,
        );

        return [
            'lote_id' => (int) $lote->id,
            'producto_id' => (int) $producto->id,
            'cliente_id' => (int) $cliente->id,
            'usuario_id' => (int) $usuario->id,
            'coherente_al_inicio' => $this->saldoCoincideConKardex((int) $lote->id),
        ];
    }

    /**
     * @param  array<string, mixed>  $escenario
     * @return array<int, array<string, string>>
     */
    private function competir(array $escenario): array
    {
        $base = config('database.connections.pgsql.database');
        $competidor = __DIR__.'/../../Soporte/Concurrencia/competidor.php';
        $barrera = microtime(true) + self::SEGUNDOS_DE_BARRERA;

        $ordenes = [];

        for ($par = 1; $par <= self::PARES; $par++) {
            // Cada ajuste apunta a una cantidad distinta para que el orden en
            // que terminen sea visible en el resultado.
            $ordenes[] = sprintf(
                '%s %s %s ajustar %F %d 1%d00.000 %d',
                PHP_BINARY, escapeshellarg($competidor), escapeshellarg($base),
                $barrera, $escenario['lote_id'], $par, $escenario['usuario_id']
            );

            $ordenes[] = sprintf(
                '%s %s %s vender %F %d %d 10.000 %d',
                PHP_BINARY, escapeshellarg($competidor), escapeshellarg($base),
                $barrera, $escenario['cliente_id'], $escenario['producto_id'], $escenario['usuario_id']
            );
        }

        $procesos = [];
        foreach ($ordenes as $orden) {
            $procesos[] = popen($orden.' 2>&1', 'r');
        }

        $respuestas = [];
        foreach ($procesos as $proceso) {
            $salida = stream_get_contents($proceso);
            pclose($proceso);
            $respuestas[] = json_decode(trim((string) strrchr("\n".trim((string) $salida), "\n")), true) ?? ['salida' => trim((string) $salida)];
        }

        return $respuestas;
    }

    /**
     * @param  array<int, array<string, string>>  $respuestas
     * @param  array<string, mixed>  $escenario
     */
    private function assertCompitieronDeVerdad(array $respuestas, array $escenario, int $ronda): void
    {
        $this->assertCount(self::PARES * 2, $respuestas, "Ronda {$ronda}: no respondieron todos los competidores.");

        foreach ($respuestas as $respuesta) {
            $this->assertArrayHasKey('operacion', $respuesta, "Ronda {$ronda}: un competidor no arrancó. Salida: ".json_encode($respuesta));

            $this->assertSame(
                config('database.connections.pgsql.database'),
                $respuesta['base'] ?? null,
                "Ronda {$ronda}: un competidor operó sobre otra base, así que no compitió con nadie."
            );
        }

        // Y dejaron rastro: si ninguna de las dos operaciones escribió, no
        // hubo nada que pudiera divergir.
        $this->assertGreaterThan(
            1,
            MovimientoInventario::query()->where('lote_id', $escenario['lote_id'])->count(),
            "Ronda {$ronda}: ninguna operación llegó a escribir en el kardex."
        );
    }

    /**
     * Ninguna operación puede fallar por competir con otra.
     *
     * Es la aserción que distingue el bloqueo de la restricción de la base, y
     * protegen cosas distintas: la unicidad de `(tipo, serie, correlativo)`
     * impide el **dato** inválido, y el bloqueo impide que la **operación**
     * falle. Sin bloqueo en la serie, medido: tres de cada cuatro ventas
     * simultáneas mueren con violación de unicidad. El dato queda íntegro y
     * tres clientes se quedaron sin comprobante después de cobrarles.
     *
     * En este escenario ninguna tiene motivo legítimo para fallar: hay stock
     * de sobra y los ajustes son válidos.
     *
     * @param  array<int, array<string, string>>  $respuestas
     */
    private function assertNingunaOperacionSeCayo(array $respuestas, int $ronda): void
    {
        $caidas = array_values(array_filter(
            $respuestas,
            fn (array $r) => ($r['respuesta'] ?? '') !== 'ok'
        ));

        $this->assertSame(
            [],
            $caidas,
            "Ronda {$ronda}: operaciones que murieron al competir — ".json_encode($caidas)
        );
    }

    /**
     * Dos ventas simultáneas no pueden obtener el mismo correlativo (RF-014).
     *
     * Ante SUNAT, dos comprobantes con el mismo número son dos documentos con
     * la misma identidad: uno de los dos se rechaza y esa venta queda sin
     * documento válido, después de cobrada.
     */
    private function assertCorrelativosSinRepetir(int $ronda): void
    {
        $emitidos = DB::table('comprobantes')
            ->select('tipo_comprobante', 'serie', 'correlativo')
            ->get()
            ->map(fn ($c) => $c->tipo_comprobante.'-'.$c->serie.'-'.$c->correlativo)
            ->all();

        $this->assertSame(
            count($emitidos),
            count(array_unique($emitidos)),
            "Ronda {$ronda}: se emitieron correlativos repetidos — ".implode(', ', $emitidos)
        );
    }

    private function saldoCoincideConKardex(int $loteId): bool
    {
        $lote = Lote::query()->find($loteId);

        if ($lote === null) {
            return false;
        }

        $suma = number_format(
            (float) DB::table('movimientos_inventario')->where('lote_id', $loteId)->sum('cantidad'),
            3, '.', ''
        );

        return bccomp($lote->cantidad_actual, $suma, 3) === 0;
    }

    private function assertCoherente(int $loteId, string $mensaje): void
    {
        $lote = Lote::query()->findOrFail($loteId);
        $suma = number_format(
            (float) DB::table('movimientos_inventario')->where('lote_id', $loteId)->sum('cantidad'),
            3, '.', ''
        );

        // La aserción es la comparación, no un valor fijo: cuál de los dos
        // competidores escribe último es legítimamente variable; que los dos
        // números coincidan, no.
        $this->assertSame(0, bccomp($lote->cantidad_actual, $suma, 3), $mensaje." — saldo {$lote->cantidad_actual}, kardex {$suma}");
    }
}
